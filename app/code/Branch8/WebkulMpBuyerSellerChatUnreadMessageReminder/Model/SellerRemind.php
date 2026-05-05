<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model;

use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatParticipant;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatParticipantFactory;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Exception\LockException;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\ResourceModel\Queue\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use phpseclib3\Math\BigInteger\Engines\PHP;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Helper\Logger;

class SellerRemind
{
    private LockModel $lockedModel;
    private ChatParticipant $chatParticipant;
    private Config $config;
    private Logger $logger;
    private $participantCache = [];
    private ResourceConnection $resourceConnection;
    private ChatParticipantFactory $chatParticipantFactory;
    private \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatParticipant $participantResource;
    private Mail $mail;
    private \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatParticipant\CollectionFactory $collectionFactory;

    /**
     * @param ResourceConnection $resourceConnection
     * @param \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatParticipant $chatParticipantResource
     * @param ChatParticipantFactory $chatParticipantFactory
     * @param Config $config
     * @param LockModel $lockedModel
     * @param \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatParticipant\CollectionFactory $chatParticipantCollectionFactory
     * @param Mail $mail
     * @param Logger $logger
     */
    public function __construct(
        ResourceConnection                                                                     $resourceConnection,
        \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatParticipant                   $chatParticipantResource,
        ChatParticipantFactory                                                                 $chatParticipantFactory,
        Config                                                                                 $config,
        LockModel                                                                              $lockedModel,
        \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatParticipant\CollectionFactory $chatParticipantCollectionFactory,
        Mail                                                                                   $mail,
        Logger                                                                        $logger
    )
    {
        $this->chatParticipantFactory = $chatParticipantFactory;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->config = $config;
        $this->lockedModel = $lockedModel;
        $this->collectionFactory = $chatParticipantCollectionFactory;
        $this->participantResource = $chatParticipantResource;
        $this->mail = $mail;
    }

    /**
     * @return void
     * @throws LockException
     */
    protected function lock()
    {
        /**
         * Last update is expired because deploy issue
         */
        if ($this->lockedModel->isLockExpired(LockModel::PROCESS_FLAG)) {
            $this->forceUnlock();
            return;
        }
        /**
         *
         */
        if ($this->lockedModel->isQueueLocked(LockModel::PROCESS_FLAG)) {
            throw new LockException(__('Another lock detected (
            the process unread reminder queue is in a progress).')
            );
        }

        $this->lockedModel->setIsQueueLocked(LockModel::PROCESS_FLAG, true);
    }

    /**
     * @return void
     */
    protected function unlock()
    {
        $this->lockedModel->setIsQueueLocked(LockModel::PROCESS_FLAG, false);
    }

    /**
     * @return void
     */
    public function forceUnlock()
    {
        $this->lockedModel->setIsQueueLocked(LockModel::PROCESS_FLAG, false);
    }

    /**
     * Generate and process queue
     *
     * @return void
     * @throws LockException
     */
    public function process()
    {
        $this->lock();
        $collection = $this->collectionFactory->create();
        $pageSize = 100;
        $maxRetried = 3;
        $interval = $this->config->getTimeInterval();
        $where = new \Zend_Db_Expr('TIMESTAMPDIFF(MINUTE, last_unread_received_at, NOW()) > ' . $interval);
        $collection
            ->addFieldToFilter('total_unread_messages', ['gt' => 0])
            ->addFieldToFilter('mail_sent', 0);
        $select = $collection->getSelect()->where($where);
        $select->join('marketplace_chat_profile_info',
            'main_table.profile_id = marketplace_chat_profile_info.entity_id',
            []
        )->where('marketplace_chat_profile_info.registered_as = ? ', ChatRole::SELLER);
        $pageCount = $collection->setPageSize($pageSize)->getLastPageNumber();
        for ($page = 1; $page <= $pageCount; $page++) {
            $collection->setCurPage($page);
            /**
             * @var $participant ChatParticipant
             */
            foreach ($collection as $participant) {
                try {
                    if ($participant->getMailSent()) {
                        throw new \Exception(__('Mail sent')->render());
                    }
                    /* if ($participant->getRetried() >= $maxRetried) {
                         throw new \Exception(__('Max Retry Send')->render());
                     }*/
                    $this->mail->send($participant);
                    $retried = $participant->getRetried() + 1;
                    $participant->setRetried($retried);
                    $participant->setMailSent(1);
                    $participant->setHasDataChanges(true);
                    // $participant->save();
                } catch (\Exception  $exception) {
                    $this->logger->critical($exception->getMessage());
                }
                $this->participantResource->save($participant);
            }
            $collection->clear();
        }
        $this->unlock();
    }
}
