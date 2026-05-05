<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Api;

use Branch8\WebkulMpBuyerSellerChat\Api\Data\MessageDataInterface;
use Branch8\WebkulMpBuyerSellerChat\Api\Data\MessageMetaInformationInterface;
use Branch8\WebkulMpBuyerSellerChat\Api\SaveMessageInterface;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\ConversationMessagesTotalCalculate;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\GetOrCreateChatProfile;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\MessageReminderManagement;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\WrapperMessage;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\SaveTotalUnreadMessagesForParticipant;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\SendSellerUnreadChatEmail;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\MessageData;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\MessageDataFactory;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatConversationRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Branch8\WebkulMpBuyerSellerChat\Model\GeneralConfig;
use Branch8\WebkulMpBuyerSellerChat\Model\MessageRawHandler;
use Branch8\WebkulMpBuyerSellerChat\Model\MessageStateTrackingManager;
use Branch8\WebkulMpBuyerSellerChat\Model\MessageType;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Branch8\WebkulMpBuyerSellerChat\Helper\Logger as CustomLogger;
use Webkul\MpBuyerSellerChat\Model\MessageRepository;

/**
 * Save message V2
 */
class SaveMessage implements SaveMessageInterface
{
    /**
     * @var string[]
     */
    private $allowMessages = [
        'text',
        'html',
        'image'
    ];
    /**
     * @var MessageRepository
     */
    private $messageRepository;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;
    /**
     * @var
     */
    private $messageFactory;


    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $encoder;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    private $customerSessionFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    private $customerFactory;

    /**
     * @var SerializerJson
     */
    private $serializerJson;

    private ChatConversationRepository $chatConversationRepository;
    private MessageRawHandler $messageRawHandler;
    private MessageStateTrackingManager $messageStateTrackingManager;

    private $chatProfileRepository;

    private CustomLogger $logger;

    private CustomerRepository $customerRepository;

    private GetOrCreateChatProfile $getOrCreateChatProfile;
    private ConversationMessagesTotalCalculate $conversationMessagesTotalCaculate;
    private $saveTotalUnreadMessageForParticipant;

    private MessageReminderManagement $messageReminderManagement;
    private SendSellerUnreadChatEmail $sendSellerUnreadChatEmail;
    private Escaper $escaper;
    private GeneralConfig $generalConfig;

    /**
     * @param CustomerRepository $customerRepository
     * @param ChatProfileRepository $chatProfileRepository
     * @param MessageRepository $messageRepository
     * @param MessageDataFactory $messageFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Url\EncoderInterface $encoder
     * @param \Magento\Customer\Model\SessionFactory $customerSessionFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param ChatConversationRepository $chatConversationRepository
     * @param MessageRawHandler $messageRawHandler
     * @param MessageStateTrackingManager $messageStateTrackingManager
     * @param SerializerJson $serializerJson
     * @param GetOrCreateChatProfile $getOrCreateChatProfile
     * @param ConversationMessagesTotalCalculate $conversationMessagesTotalCalculate
     * @param SaveTotalUnreadMessagesForParticipant $saveTotalUnreadMessageForParticipant
     * @param MessageReminderManagement $messageReminderManagement
     * @param GeneralConfig $generalConfig
     * @param CustomLogger $logger
     * @param SendSellerUnreadChatEmail $sendSellerUnreadChatEmail
     * @param Escaper $escaper
     */
    public function __construct(
        CustomerRepository                      $customerRepository,
        ChatProfileRepository                   $chatProfileRepository,
        MessageRepository                       $messageRepository,
        MessageDataFactory                      $messageFactory,
        DataObjectHelper                        $dataObjectHelper,
        \Magento\Framework\Url\EncoderInterface $encoder,
        \Magento\Customer\Model\SessionFactory  $customerSessionFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        ChatConversationRepository              $chatConversationRepository,
        MessageRawHandler                       $messageRawHandler,
        MessageStateTrackingManager             $messageStateTrackingManager,
        SerializerJson                          $serializerJson,
        GetOrCreateChatProfile                  $getOrCreateChatProfile,
        ConversationMessagesTotalCalculate      $conversationMessagesTotalCalculate,
        SaveTotalUnreadMessagesForParticipant   $saveTotalUnreadMessageForParticipant,
        MessageReminderManagement               $messageReminderManagement,
        GeneralConfig                           $generalConfig,
        CustomLogger                            $logger,
        SendSellerUnreadChatEmail               $sendSellerUnreadChatEmail,
        Escaper                                 $escaper
    )
    {
        $this->getOrCreateChatProfile = $getOrCreateChatProfile;
        $this->customerRepository = $customerRepository;
        $this->messageFactory = $messageFactory;
        $this->encoder = $encoder;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->messageRepository = $messageRepository;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->chatConversationRepository = $chatConversationRepository;
        $this->messageRawHandler = $messageRawHandler;
        $this->serializerJson = $serializerJson;
        $this->customerFactory = $customerFactory;
        $this->messageStateTrackingManager = $messageStateTrackingManager;
        $this->chatProfileRepository = $chatProfileRepository;
        $this->conversationMessagesTotalCaculate = $conversationMessagesTotalCalculate;
        $this->saveTotalUnreadMessageForParticipant = $saveTotalUnreadMessageForParticipant;
        $this->messageReminderManagement = $messageReminderManagement;
        $this->logger = $logger;
        $this->sendSellerUnreadChatEmail = $sendSellerUnreadChatEmail;
        $this->escaper = $escaper;
        $this->generalConfig = $generalConfig;
    }

    /**
     * @param $uniqueId
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerFromUniqId($uniqueId)
    {
        try {
            $profile = $this->chatProfileRepository->getByUniqueId($uniqueId);
            if (!$profile || !$profile->getId()) {
                throw new LocalizedException(
                    __('Empty profile for uniqueId %1', $uniqueId)
                );
            }
            return $this->customerRepository->getById($profile->getObjectId());
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param $conversationId
     * @param $senderUniqueId
     * @param $receiverUniqueId
     * @param $message
     * @param $dateTime
     * @param $msgType
     * @param string|null $uniqueId
     * @param array|null $meta
     * @param $productId
     * @return bool|mixed|string|void
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveChatMessage(
        $conversationId,
        $senderUniqueId,
        $receiverUniqueId,
        $message,
        $dateTime,
        $msgType,
        string $uniqueId = null,
        array $meta = null,
        $productId = null
    )
    {
        $uniqueId = $uniqueId ? $uniqueId : uniqid();
        $chatConversation = $this->chatConversationRepository->loadConversationByCode($conversationId);
        $customerId = $this->customerSessionFactory->create()->getCustomer()->getId();
        $senderProfile = $this->chatProfileRepository->getByUniqueId($senderUniqueId);
        $receiverProfile = $this->chatProfileRepository->getByUniqueId($receiverUniqueId);
        $customer = $this->customerFactory->create()->load($customerId);
        $mCustomerSender = $this->getCustomerFromUniqId($senderUniqueId);
        $mReceiverSender = $this->getCustomerFromUniqId($receiverUniqueId);
        $autoMarkReadMessAsRead =$this->generalConfig->getAutoMarkReadMessageType();
        if (!$msgType || ($msgType && !in_array($msgType, MessageType::getValidTypes()))) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Invalid Message Type')
            );
        }
        $res = [
            'errors' => false,
            'messages' => []
        ];
        if ($customer) {
            $meta = $meta ? $this->serializerJson->serialize($this->processMetaInput($meta)) : '[]';
            $message = [
                'conversation_id' => $chatConversation->getConversationId(),
                'conversation_unique_id' => $chatConversation->getConversationUniqueId(),
                'sender_unique_id' => $senderProfile->getUniqueId(),
                'receiver_unique_id' => $receiverProfile->getUniqueId(),
                'message_type' => $msgType,
                'unique_id' => $uniqueId,
                'message' => $this->sanitize($message),
                'date' => $dateTime,
                'sender_name' => $senderProfile->getNickName() ? $senderProfile->getNickName() : ChatRole::getNameFromCustomer($mCustomerSender),
                'receiver_name' => $receiverProfile->getNickName() ? $receiverProfile->getNickName() : ChatRole::getNameFromCustomer($mReceiverSender),
                'meta' => $meta,
                'product_id' => $productId
            ];
            $dataObject = $this->messageFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $dataObject,
                $message,
                MessageDataInterface::class
            );
            try {
                $dataObject->setData('conversation_id', $chatConversation->getConversationId());
                $dataObject->setData('conversation_unique_id', $chatConversation->getConversationUniqueId());
                $dataObject->setData('message_type', $msgType);
                $dataObject->setData('meta', $meta);
                $dataObject->setData('unique_id', $uniqueId);
                $this->messageRepository->save($dataObject);
                $this->conversationMessagesTotalCaculate->execute((int)$chatConversation->getConversationId());
                $this->messageStateTrackingManager->setState(
                    $dataObject,
                    in_array($msgType, $autoMarkReadMessAsRead) ? MessageStateTrackingManager::READ : MessageStateTrackingManager::UNREAD
                );
                $this->saveTotalUnreadMessageForParticipant->execute(
                    (int)$chatConversation->getConversationId(),
                    (int)$receiverProfile->getId(),
                    (string)$receiverProfile->getUniqueId(),
                    true
                );

                //TODO: use queue instead for better performance
                /**
                 * 2025-01-01 use chat_unreadmessage_remind_process cron
                 */
                /* if ($receiverProfile->getRegisteredAs() == 'seller') {
                     $this->notifySeller($receiverProfile->getObjectId(), $dataObject);
                 }*/

            } catch (\Exception $e) {
                $res['errors'] = true;
                $this->logger->critical($e->getMessage());
                $this->logger->info($e->getTraceAsString());
            }

            return $this->serializerJson->serialize($res);
        }
    }

    /**
     * @param $message
     * @return string
     */
    private function sanitize($message)
    {
        return WrapperMessage::sanitize($this->escaper->escapeJs($message));
    }
    /**
     * TODO: use queue instead for better performance
     * @param $sellerId
     * @param MessageData | MessageDataInterface $messageData
     * @return void
     */
    public function notifySeller($sellerId, $messageData)
    {
        try {
            $this->sendSellerUnreadChatEmail->send((int)$sellerId, $messageData);
        } catch (\Exception $e) {
            $this->logger->error("[BuyerSellerChat] Can't send email to seller: " . $e->getMessage());
        }
    }

    /**
     * @param $meta
     * @return array
     */
    private function processMetaInput($meta = [])
    {
        $data = [];
        /**
         * @var $item MessageMetaInformationInterface
         */
        foreach ($meta as $item) {
            $data[] = ['key' => $item->getKey(), 'value' => $item->getValue()];
        }
        return $data;
    }
}
