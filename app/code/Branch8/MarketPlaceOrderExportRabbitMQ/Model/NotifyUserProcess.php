<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model;

use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Queue\MailPublish;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatParticipantFactory;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Exception\LockException;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\ResourceModel\Queue\CollectionFactory;
use Psr\Log\LoggerInterface;

class NotifyUserProcess
{

    private MailPublish $publish;

    private ResourceModel\Profile\CollectionFactory $collectionFactory;

    private LockModel $lockModel;
    private LoggerInterface $logger;

    //**

    public function __construct(
        \Branch8\MarketPlaceOrderExportRabbitMQ\Model\LockModel                               $lockModel,
        \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile\CollectionFactory $collectionFactory,
        LoggerInterface                                                                       $logger,
        MailPublish                                                                           $publish
    )
    {
        $this->logger = $logger;
        $this->publish = $publish;
        $this->collectionFactory = $collectionFactory;
        $this->lockModel = $lockModel;
    }

    /**
     * @return void
     * @throws LockException
     */
    protected function lock()
    {
        if ($this->lockModel->isQueueLocked(LockModel::PROCESS_FLAG)) {
            throw new LockException(__('Another lock detected (
            the process unread reminder queue is in a progress).')
            );
        }

        $this->lockModel->setIsQueueLocked(LockModel::PROCESS_FLAG, true);
    }

    /**
     * @return void
     */
    protected function unlock()
    {
        $this->lockModel->setIsQueueLocked(LockModel::PROCESS_FLAG, false);
    }

    /**
     * @return void
     */
    public function forceUnlock()
    {
        $this->lockModel->setIsQueueLocked(LockModel::PROCESS_FLAG, false);
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
        $collection
            ->addFieldToFilter('email_sent', ['eq' => 0])
            ->addFieldToFilter('status', 'done');
        $pageCount = $collection->setPageSize($pageSize)->getLastPageNumber();
        for ($page = 1; $page <= $pageCount; $page++) {
            $collection->setCurPage($page);
            /**
             * @var $profile Profile
             */
            foreach ($collection as $profile) {
                try {
                    if ($profile->getEmailSent()) {
                        throw new \Exception(__('Mail sent')->render());
                    }
                    $this->publish->execute($profile);
                    $profile->setData('email_sent', 1)->getResource()->save($profile);
                } catch (\Exception  $exception) {
                    if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceOrderExportRabbitMQ', 'exceptionlog')){
                        $this->logger->critical($exception->getMessage());
                    }
                }
                $profile->getResource()->save($profile);
            }
            $collection->clear();
        }
        $this->unlock();
    }
}
