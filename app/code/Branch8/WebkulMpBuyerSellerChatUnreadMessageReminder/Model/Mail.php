<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model;

use Branch8\WebkulMpBuyerSellerChat\Model\Actions\ParticipantFinders;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatParticipant;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\Actions\GetCcAddress;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\Actions\GetLastUnreadMessage;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Area;
use Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Helper\Logger;

class Mail implements MailInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StateInterface
     */
    private $inlineTranslation;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    private mixed $chatParticipantFinder;
    private Logger $logger;
    private GetLastUnreadMessage $lastUnreadMessage;
    private ScopeConfigInterface $scopeConfig;

    protected UrlInterface $urlBuilder;
    private SerializerJson $serializerJson;
    private \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\MessageDataFactory $messageDataFactory;
    private GetCcAddress $getCcAddress;

    /**
     * @param Config $config
     * @param ParticipantFinders $participantFinders
     * @param GetLastUnreadMessage $lastUnreadMessage
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerJson $serializerJson
     * @param UrlInterface $urlBuilder
     * @param \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\MessageDataFactory $messageDataFactory
     * @param GetCcAddress $getCcAddress
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        Config                                                             $config,
        ParticipantFinders                                                 $participantFinders,
        GetLastUnreadMessage                                               $lastUnreadMessage,
        TransportBuilder                                                   $transportBuilder,
        StateInterface                                                     $inlineTranslation,
        Logger                                                    $logger,
        ScopeConfigInterface                                               $scopeConfig,
        SerializerJson                                                     $serializerJson,
        UrlInterface                                                       $urlBuilder,
        \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\MessageDataFactory $messageDataFactory,
        GetCcAddress                                                       $getCcAddress,
        StoreManagerInterface                                              $storeManager = null
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->lastUnreadMessage = $lastUnreadMessage;
        $this->logger = $logger;
        $this->chatParticipantFinder = $participantFinders;
        $this->config = $config;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->serializerJson = $serializerJson;
        $this->urlBuilder = $urlBuilder;
        $this->messageDataFactory = $messageDataFactory;
        $this->getCcAddress = $getCcAddress;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * @param ChatParticipant $chatParticipant
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function send(ChatParticipant $chatParticipant)
    {
        /** @see \Magento\Contact\Controller\Index\Post::validatedParams() */
        $chatProfile = $chatParticipant->getChatProfileInformation();
        $chatConversation = $chatParticipant->getChatConversation();
        $totalUnreadMessages = $chatParticipant->getTotalUnreadMessages();
        $otherParticipantName = '';
        $ccAddresses = $this->getCcAddress->get($chatProfile);
        if (!$chatProfile->getId() || !$chatProfile->getEmail()) {
            throw new \Exception(__('Invalid chat profile ')->render());
        }
        if (!$chatConversation->getId()) {
            throw new \Exception(__('Invalid chat conversation ')->render());
        }
        if ($chatParticipant->getTotalUnreadMessage() <= 0) {
            throw new \Exception(__('Total unread message less than equal zero')->render());
        }
        $lastUnreadMessage = (int)$chatParticipant->getLastUnReadMessage();
        /**
         * @var $lastUnreadMessageData \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\MessageData
         */
        $lastUnreadMessageData = $this->messageDataFactory->create()->load($lastUnreadMessage);
        if (!$lastUnreadMessageData || !$lastUnreadMessageData->getId()) {
            $this->logger->critical(__('Invalid Message')->render());
            return;
        }
        if ($lastUnreadMessageData->isRead()) {
            $this->logger->critical(__('Message is read %1', $lastUnreadMessage)->render());
            return;
        }
        $collections = $this->chatParticipantFinder->find(
            $chatConversation,
            [$chatParticipant->getProfileId()]
        );
        if ($collections->getSize()
            && ($otherParticipantName = $collections->getFirstItem()->getChatProfileInformation())
            && $otherParticipantName->getId()
            && $otherParticipantName->getName()
        ) {
            $otherParticipantName = $otherParticipantName->getName();
        }
        $this->inlineTranslation->suspend();
        try {
            $object = new DataObject([
                'participant_name' => $chatParticipant->getChatProfileInformation()->getName(),
                'other_participant_name' => $otherParticipantName
            ]);
            $variables = [
                'data' => $object,
                'seller_name' => $chatProfile->getName(),
                'message' => $this->prepareEmbedMessage($lastUnreadMessageData),
                'chat_url' => $this->getSellerChatUrl()
            ];
            if ($totalUnreadMessages > 0) {
                $variables['total_unread_messages'] = $totalUnreadMessages;
            }
            if ($lastUnreadMessage > 0) {
                $variables['last_unread_message'] = $lastUnreadMessage;
            }
            $transportBuilder = $this->transportBuilder
                ->setTemplateIdentifier($this->config->emailTemplate())
                ->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $this->storeManager->getStore()->getId()
                    ]
                )
                ->setTemplateVars($variables)
                ->setFrom($this->config->emailSender())
                ->addTo($chatProfile->getEmail(), $chatProfile->getName());
            if ($ccAddresses) {
                foreach ($ccAddresses as $address) {
                    $transportBuilder->addCc($address['email'], $address['name']);
                }
            }
            $transport = $transportBuilder->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        } finally {
            $this->inlineTranslation->resume();
        }
    }

    /**
     * @param $messageData
     * @return string
     */
    private function prepareEmbedMessage($messageData)
    {
        switch ($messageData->getData('message_type')) {
            case 'text':
                return "<p style='margin:0; padding:0;'>{$messageData->getMessage()}</p>";
            case 'image':
                $meta = $this->serializerJson->unserialize($messageData->getMeta());
                $name = $this->getMetaValue($meta, 'name');
                $url = $this->getMetaValue($meta, 'url');
                return "<a href='{$url}'><img style='max-width: 300px' src='{$url}' alt='{$name}'></a>";


            case 'html':
                return $messageData->getMessage(); //TODO css
            case 'video':
                $meta = $this->serializerJson->unserialize($messageData->getMeta());
                $url = $this->getMetaValue($meta, 'url');
                $name = $this->getMetaValue($meta, 'name');

                //fake video thumbnail TODO
                return "<a href='{$url}'><img src='https://via.placeholder.com/150' alt='{$name}'></a>";
                break;
            default:
                return '<a href="' . $this->getSellerChatUrl() . '">' . $messageData->getMessage() . '</a>';
        }
    }

    /**
     * @return mixed
     */
    private function getSellerChatUrl()
    {
        return $this->urlBuilder->getUrl('marketplace/account/dashboard');
    }

    /**
     * @param $meta
     * @param $key
     * @return string
     */
    private function getMetaValue($meta, $key)
    {
        foreach ($meta as $item) {
            if ($item['key'] == $key) {
                return $item['value'];
            }
        }
        return '';
    }
}
