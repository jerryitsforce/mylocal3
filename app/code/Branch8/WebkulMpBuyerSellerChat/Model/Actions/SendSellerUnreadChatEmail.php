<?php
namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;
use Branch8\WebkulMpBuyerSellerChat\Api\Data\MessageDataInterface;
use Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatProfileInformation\CollectionFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Branch8\WebkulMpBuyerSellerChat\Helper\Logger as CustomLogger;
use Webkul\MpBuyerSellerChat\Helper\Data as ChatHelper;

class SendSellerUnreadChatEmail
{

    protected StoreManagerInterface $storeManager;

    protected ScopeConfigInterface $scopeConfig;

    protected TransportBuilder $transportBuilder;

    protected StateInterface $inlineTranslation;

    protected CustomerFactory $customerFactory;


    protected ChatHelper $chatHelper;
    protected GetUserChatStatus $getUserChatStatus;
    protected CustomLogger $logger;
    protected SerializerJson $serializerJson;
    protected UrlInterface $urlBuilder;


    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        CustomerFactory $customerFactory,
        ChatHelper $chatHelper,
        GetUserChatStatus $getUserChatStatus,
        CustomLogger $logger,
        SerializerJson $serializerJson,
        UrlInterface $urlBuilder,
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->customerFactory = $customerFactory;
        $this->chatHelper = $chatHelper;
        $this->getUserChatStatus = $getUserChatStatus;
        $this->logger = $logger;
        $this->serializerJson = $serializerJson;
        $this->urlBuilder = $urlBuilder;
    }


    /**
     * @param int $sellerId
     * @param MessageDataInterface $messageData
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function send(int $sellerId, MessageDataInterface $messageData)
    {
        if($this->isSellerAvailable($sellerId)){
            return;
        }

        $seller = $this->customerFactory->create()->load($sellerId);

        if (!$seller->getId()) {
            return;
        }

        $adminEmail = $this->scopeConfig->getValue(
            'trans_email/ident_support/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $adminName  = $this->scopeConfig->getValue(
            'trans_email/ident_support/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $configVal = $this->scopeConfig->getValue(
            'buyer_seller_chat/email/offline_seller_email_template',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );




        $templateVars = [
            'seller_name' => $seller->getName(),
            'message' => $this->prepareEmbedMessage($messageData),
            'chat_url' => $this->getSellerChatUrl()
        ];
        $transport = $this->transportBuilder->setTemplateIdentifier($configVal)
            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId()])
            ->setTemplateVars($templateVars)
            ->setFrom(['email' => $adminEmail, 'name' => $adminName])
            ->addTo($seller->getEmail(), $seller->getName())
            ->getTransport();

        try {
            $transport->sendMessage();
        } catch (\Exception $ex) {
            $this->logger->info($ex->getMessage());
        }
        $this->inlineTranslation->resume();
    }


    public function isSellerAvailable($sellerId, )
    {
        $isAvailable = $this->getUserChatStatus->execute($sellerId, 'seller');
        return $isAvailable == 1 || $isAvailable == 2;
    }

    public function getSellerChatUrl()
    {
        return $this->urlBuilder->getUrl('marketplace/account/dashboard');
    }

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
                return '<a href="' . $this->getSellerChatUrl() .  '">' . $messageData->getMessage() . '</a>';
        }
    }




    public function getMetaValue($meta, $key)
    {
        foreach ($meta as $item) {
            if ($item['key'] == $key) {
                return $item['value'];
            }
        }
        return '';
    }
}
