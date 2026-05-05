<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Api;

use Branch8\WebkulMpBuyerSellerChat\Api\LoadChatHistoryInterface;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\SaveTotalUnreadMessagesForParticipant;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\ConversationVerification;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatConversationRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileEntity;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\GetOrCreateChatProfile;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Branch8\WebkulMpBuyerSellerChat\Model\MessageRawHandler;
use Branch8\WebkulMpBuyerSellerChat\Model\MessageStateTrackingManager;
use Branch8\WebkulMpBuyerSellerChat\Model\MessageType;
use Laminas\Db\Sql\Select;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;

class LoadChatHistory implements LoadChatHistoryInterface
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    protected $urlDecoder;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $filesystemIo;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var SerializerJson
     */
    protected $serializerJson;
    /**
     * @var MessageRawHandler
     */
    private MessageRawHandler $messageRawHandler;

    private MessageStateTrackingManager $messageStateTrackingManager;

    private $customerSession;

    private GetOrCreateChatProfile $getOrCreateChatProfile;
    private ChatConversationRepository $chatConversationRepository;
    private ChatProfileRepository $chatProfileRepository;
    private ConversationVerification $converstationVerification;

    private SaveTotalUnreadMessagesForParticipant $totalUnreadMessageForParticipant;

    /**
     * @param Session $customerSession
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Filesystem\Io\File $filesystemIo
     * @param \Magento\Framework\Filesystem $filesystem
     * @param SerializerJson $serializerJson
     * @param MessageRawHandler $messageRawHandler
     * @param MessageStateTrackingManager $messageStateTrackingManager
     * @param ChatConversationRepository $chatConversationRepository
     * @param GetOrCreateChatProfile $getOrCreateChatProfile
     * @param ChatProfileRepository $chatProfileRepository
     * @param ConversationVerification $conversationVerification
     * @param SaveTotalUnreadMessagesForParticipant $totalUnreadMessageForParticipant
     */
    public function __construct(
        Session                                     $customerSession,
        \Magento\Framework\Url\DecoderInterface     $urlDecoder,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\ResourceConnection   $resourceConnection,
        \Magento\Framework\Filesystem\Io\File       $filesystemIo,
        \Magento\Framework\Filesystem               $filesystem,
        SerializerJson                              $serializerJson,
        MessageRawHandler                           $messageRawHandler,
        MessageStateTrackingManager                 $messageStateTrackingManager,
        ChatConversationRepository                  $chatConversationRepository,
        GetOrCreateChatProfile                      $getOrCreateChatProfile,
        ChatProfileRepository                       $chatProfileRepository,
        ConversationVerification                    $conversationVerification,
        SaveTotalUnreadMessagesForParticipant $totalUnreadMessageForParticipant
    )
    {
        $this->date = $date;
        $this->urlDecoder = $urlDecoder;
        $this->resourceConnection = $resourceConnection;
        $this->filesystemIo = $filesystemIo;
        $this->filesystem = $filesystem;
        $this->serializerJson = $serializerJson;
        $this->messageRawHandler = $messageRawHandler;
        $this->messageStateTrackingManager = $messageStateTrackingManager;
        $this->customerSession = $customerSession;
        $this->getOrCreateChatProfile = $getOrCreateChatProfile;
        $this->chatConversationRepository = $chatConversationRepository;
        $this->chatProfileRepository = $chatProfileRepository;
        $this->converstationVerification = $conversationVerification;
        $this->totalUnreadMessageForParticipant = $totalUnreadMessageForParticipant;
    }

    /**
     * @param string $chatProfileId
     * @param string $conversationUniqueId
     * @param string $anchorDate
     * @param string $direction
     * @return bool|mixed|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function load(
        string $chatProfileId,
        string $conversationUniqueId,
        string $anchorDate,
        string $direction = self::PREVIOUS
    )
    {
        /**
         * @var Select $select
         * @var Select $countSelect
         */
        $currentChatProfile = $this->chatProfileRepository->getByUniqueId(
            $chatProfileId
        );
        $conversation = $this->chatConversationRepository->loadConversationByCode(
            $conversationUniqueId
        );
        $connection = $this->resourceConnection->getConnection();
        /**
         * @var $countSelect Select
         * @var $limit Select
         */
        list($countSelect, $select) = $this->getSelect(
            (int)$conversation->getId(),
            $anchorDate,
            $direction
        );
        $select->limit(self::DEFAULT);
        $select->order('date ASC');
        $rowCount = $connection->fetchOne($countSelect);
        $result = $connection->fetchAssoc($select);
        $this->messageStateTrackingManager->checkAndMarkAsRead(
            $currentChatProfile->getUniqueId(), $result
        );
        $this->totalUnreadMessageForParticipant->execute(
            (int)$conversation->getId(),
            (int)$currentChatProfile->getId(),
            (string)$currentChatProfile->getUniqueId()
        );
        $messages = $this->messageRawHandler->handle($result);
        $response = [
            'totalItem' => $rowCount,
            'messages' => $messages
        ];
        return $this->serializerJson->serialize($response);
    }

    /**
     * @param int $conversationId
     * @param string $anchorDate
     * @param string $direction
     * @return array
     */
    private function getSelect(
        int    $conversationId,
        string $anchorDate,
        string $direction
    )
    {
        $originQuery = "(conversation_id=" . $conversationId . ")";
        if ($direction === self::PREVIOUS) {
            $queryFilter = $originQuery . ' AND date < \'' . $anchorDate . '\'';
        } else {
            $queryFilter = $originQuery . ' AND date > \'' . $anchorDate . '\'';
        }
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $historyTable = $connection->getTableName('marketplace_chat_history');
        $messageRecipientTable = $connection->getTableName(
            'marketplace_chat_message_recipient'
        );
        $select->from($historyTable)
            ->joinLeft(
                $messageRecipientTable,
                'marketplace_chat_history.entity_id=marketplace_chat_message_recipient.message_chat_history_id',
                [
                    'is_read' => 'marketplace_chat_message_recipient.is_read',
                    'messageRecipientReceiver' => 'marketplace_chat_message_recipient.receiver_unique_id'
                ]
            )->where(new \Zend_Db_Expr($queryFilter));
        $countSelect = $connection->select()
            ->from($historyTable, array('count' => 'COUNT(*)'))
            ->where($originQuery);
        return [
            $countSelect,
            $select
        ];
    }
}
