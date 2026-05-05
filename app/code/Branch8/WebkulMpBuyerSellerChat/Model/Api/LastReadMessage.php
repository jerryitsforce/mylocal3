<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Api;

use AllowDynamicProperties;
use Branch8\WebkulMpBuyerSellerChat\Api\LastReadMessageInterface;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\SaveTotalUnreadMessagesForParticipant;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatParticipantFactory;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\MessageDataFactory;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatConversationRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\MessageStateTrackingManager;
use Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatParticipant;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Branch8\WebkulMpBuyerSellerChat\Helper\Logger as CustomLogger;


/**
 * Save message V2
 */
#[AllowDynamicProperties] class LastReadMessage implements LastReadMessageInterface
{
    private ChatParticipantFactory $chatParticipantFactory;
    private ChatParticipant $chatParticipantResource;
    private ChatProfileRepository $chatProfileRepository;

    private ChatConversationRepository $chatConversationRepository;

    private $messageMetaInformationFactory;

    private $messageStateTrackingManager;

    private $resourceConnection;

    private $saveTotalUnreadMessagesForParticipant;

    private CustomLogger $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MessageDataFactory $messageDataFactory
     * @param ChatConversationRepository $chatConversationRepository
     * @param ChatProfileRepository $chatProfileRepository
     * @param ChatParticipantFactory $chatParticipantFactory
     * @param ChatParticipant $chatParticipantResource
     * @param Data\MessageMetaInformationFactory $messageMetaInformationFactory
     * @param MessageStateTrackingManager $messageStateTrackingManager
     * @param SaveTotalUnreadMessagesForParticipant $saveTotalUnreadMessagesForParticipant
     * @param CustomLogger $logger
     */
    public function __construct(
        ResourceConnection                                                            $resourceConnection,
        MessageDataFactory                                                            $messageDataFactory,
        ChatConversationRepository                                                    $chatConversationRepository,
        ChatProfileRepository                                                         $chatProfileRepository,
        ChatParticipantFactory                                                        $chatParticipantFactory,
        ChatParticipant                                                               $chatParticipantResource,
        \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\MessageMetaInformationFactory $messageMetaInformationFactory,
        MessageStateTrackingManager                                                   $messageStateTrackingManager,
        SaveTotalUnreadMessagesForParticipant                                         $saveTotalUnreadMessagesForParticipant,
        CustomLogger $logger
    )
    {
        $this->chatConversationRepository = $chatConversationRepository;
        $this->messageDataFactory = $messageDataFactory;
        $this->chatProfileRepository = $chatProfileRepository;
        $this->chatParticipantFactory = $chatParticipantFactory;
        $this->messageMetaInformationFactory = $messageMetaInformationFactory;
        $this->chatParticipantResource = $chatParticipantResource;
        $this->messageStateTrackingManager = $messageStateTrackingManager;
        $this->saveTotalUnreadMessagesForParticipant = $saveTotalUnreadMessagesForParticipant;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * @param string $conversationId
     * @param string $profileUniqueId
     * @param string $messageUniqueId
     * @return DataObject
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(
        string $conversationId,
        string $profileUniqueId,
        string $messageUniqueId
    )
    {
        $chatProfile = $this->chatProfileRepository->getByUniqueId($profileUniqueId);
        $message = $this->messageDataFactory->create()->loadByUniqueId($messageUniqueId);
        $chatConversation = $this->chatConversationRepository->loadConversationByCode($conversationId);
        $chatParticipant = $this->chatParticipantFactory->create()->loadByConversationAndProfileId(
            (int)$chatConversation->getId(),
            (int)$chatProfile->getId()
        );
        if (!$message->getId()) {
            throw new NoSuchEntityException(__('Not message found: %1', $messageUniqueId));
        }
        if (!$chatParticipant || !$chatParticipant->getId()) {
            throw new NoSuchEntityException(__('Not chat participant found: profileID:%1-conversationId:%2',$profileUniqueId, $conversationId));
        }
        $chatParticipant->setLastReadMessage($message->getId());
        $this->chatParticipantResource->save($chatParticipant);
        $meta = $this->messageMetaInformationFactory->create();
        /*$this->messageStateTrackingManager->setState(
            $message,
            MessageStateTrackingManager::READ
        );*/
        $this->messageStateTrackingManager->markMessagesAsRead(
            $chatConversation->getConversationId(),
            $profileUniqueId,
            $message->getId()
        );
        $this->saveTotalUnreadMessagesForParticipant->execute(
            (int)$chatConversation->getConversationId(),
            (int)$chatProfile->getId(),
            (string)$chatProfile->getUniqueId()
        );
        $meta->setKey('lastReadMessage');
        $meta->setValue($messageUniqueId);
        return $meta;
    }

    private function markPreviousChatMessageIsRead($anchor)
    {
        $connection = $this->resourceConnection->getConnection();
    }
}
