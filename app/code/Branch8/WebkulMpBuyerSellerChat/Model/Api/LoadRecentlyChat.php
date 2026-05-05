<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Api;

use Branch8\WebkulMpBuyerSellerChat\Api\LoadRecentlyChatInterface;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\ReadTotalUnreadMessages;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\SaveTotalUnreadMessagesForParticipant;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\ConversationVerification;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatParticipantFactory;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatConversationRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\GetOrCreateChatProfile;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\MessageRawHandler;
use Branch8\WebkulMpBuyerSellerChat\Model\MessageStateTrackingManager;
use Laminas\Db\Sql\Select;
use Magento\Customer\Model\Session;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Branch8\WebkulMpBuyerSellerChat\Helper\Logger as CustomLogger;

/**
 * Load recently chat for profile
 */
class LoadRecentlyChat implements LoadRecentlyChatInterface
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
    private MessageRawHandler $messageRawHandler;
    private Session $customerSession;

    private GetOrCreateChatProfile $getOrCreateChatProfile;
    private MessageStateTrackingManager $messageStateTrackingManager;
    private ChatConversationRepository $chatConversationRepository;
    private ChatProfileRepository $chatProfileRepository;
    private ConversationVerification $converstationVerification;
    private SaveTotalUnreadMessagesForParticipant $participantTotalUnreadMessages;

    private $chatParticipantFactory;
    private CustomLogger $logger;

    private ReadTotalUnreadMessages $readTotalUnreadMessages;

    /**
     * @param Session $session
     * @param ChatConversationRepository $chatConversationRepository
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Filesystem\Io\File $filesystemIo
     * @param \Magento\Framework\Filesystem $filesystem
     * @param SerializerJson $serializerJson
     * @param MessageRawHandler $messageRawHandler
     * @param GetOrCreateChatProfile $getOrCreateChatProfile
     * @param MessageStateTrackingManager $messageStateTrackingManager
     * @param ChatProfileRepository $chatProfileRepository
     * @param ConversationVerification $conversationVerification
     * @param ChatParticipantFactory $chatParticipantFactory
     * @param SaveTotalUnreadMessagesForParticipant $conversationUnreadMessagesTotalCalculate
     */
    public function __construct(
        Session                                     $session,
        ChatConversationRepository                  $chatConversationRepository,
        \Magento\Framework\Url\DecoderInterface     $urlDecoder,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\ResourceConnection   $resourceConnection,
        \Magento\Framework\Filesystem\Io\File       $filesystemIo,
        \Magento\Framework\Filesystem               $filesystem,
        SerializerJson                              $serializerJson,
        MessageRawHandler                           $messageRawHandler,
        GetOrCreateChatProfile                      $getOrCreateChatProfile,
        MessageStateTrackingManager                 $messageStateTrackingManager,
        ChatProfileRepository                       $chatProfileRepository,
        ConversationVerification                    $conversationVerification,
        ChatParticipantFactory                      $chatParticipantFactory,
        SaveTotalUnreadMessagesForParticipant       $conversationUnreadMessagesTotalCalculate,
        ReadTotalUnreadMessages                     $readTotalUnreadMessages,
        CustomLogger                                $logger
    )
    {
        $this->chatConversationRepository = $chatConversationRepository;
        $this->customerSession = $session;
        $this->date = $date;
        $this->urlDecoder = $urlDecoder;
        $this->resourceConnection = $resourceConnection;
        $this->filesystemIo = $filesystemIo;
        $this->filesystem = $filesystem;
        $this->serializerJson = $serializerJson;
        $this->messageRawHandler = $messageRawHandler;
        $this->getOrCreateChatProfile = $getOrCreateChatProfile;
        $this->messageStateTrackingManager = $messageStateTrackingManager;
        $this->chatProfileRepository = $chatProfileRepository;
        $this->converstationVerification = $conversationVerification;
        $this->chatParticipantFactory = $chatParticipantFactory;
        $this->participantTotalUnreadMessages = $conversationUnreadMessagesTotalCalculate;
        $this->readTotalUnreadMessages = $readTotalUnreadMessages;
        $this->logger = $logger;
    }

    /**
     * @param string $chatProfileId
     * @param string $conversationUniqueId
     * @param int $size
     * @return bool|mixed|string
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function load(
        string $chatProfileId,
        string $conversationUniqueId,
        int    $size = self::DEFAULT
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
        $chatParticipant = $this->chatParticipantFactory->create()->loadByConversationAndProfileId(
            $conversation->getConversationId(),
            (int)$currentChatProfile->getId()
        );
        $lastReadMessage = $chatParticipant->getLastReadMessage();
        $connection = $this->resourceConnection->getConnection();
        list($countSelect, $select) = $this->getSelect(
            (int)$conversation->getId());
        //$currentDate = $this->date->gmtDate('Y-m-d H:i:s');
        /*  $select->where('date < ?', $currentDate)
              ->order('date ASC')
              ->limit(self::DEFAULT);*/
        // $countSelect->where('date < ?', $currentDate);
        $rowCount = $connection->fetchOne($countSelect);
        $result = $connection->fetchAssoc($select);
        $this->messageStateTrackingManager->checkAndMarkAsRead(
            $currentChatProfile->getUniqueId(), $result
        );
        $this->participantTotalUnreadMessages->execute(
            (int)$conversation->getId(),
            (int)$currentChatProfile->getId(),
            (string)$currentChatProfile->getUniqueId()
        );
        $messages = array_reverse($this->messageRawHandler->handle($result));
        $response = [
            'totalUnreadMessages' => $this->readTotalUnreadMessages->get(
                $chatProfileId,
                $conversationUniqueId
            ),
            'totalItem' => $rowCount,
            'messages' => $messages
        ];
        return $this->serializerJson->serialize($response);
    }

    /**
     * @param int $conversationId
     * @param $lastLoadedMessageId
     * @return array
     */
    private function getSelect(int $conversationId, $lastLoadedMessageId = null)
    {
        $queryString = "(conversation_id=" . $conversationId . ")";
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $historyTable = $connection->getTableName('marketplace_chat_history');
        $messageRecipientTable = $connection->getTableName('marketplace_chat_message_recipient');
        $select->from($historyTable)
            ->joinLeft(
                $messageRecipientTable,
                'marketplace_chat_history.entity_id = marketplace_chat_message_recipient.message_chat_history_id',
                [
                    'is_read' => 'marketplace_chat_message_recipient.is_read',
                    'messageRecipientReceiver' => 'marketplace_chat_message_recipient.receiver_unique_id'
                ]
            )->where(new \Zend_Db_Expr($queryString))->order('date DESC');
        if ($lastLoadedMessageId) {
            $select->where('entity_id < ? ', $lastLoadedMessageId);
        }
        $select->limit(self::DEFAULT);
        $countSelect = $connection->select()
            ->from($historyTable, array('count' => 'COUNT(*)'))
            ->where($queryString);
        return [
            $countSelect,
            $select
        ];
    }
}
