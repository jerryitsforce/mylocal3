<?php

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Plugin\Branch8\Marketplace\Controller\Order;

use Branch8\MarketPlaceOrderExportRabbitMQ\Model\NotificationConfig;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileNotification;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileNotificationFactory;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Queue\Notification\Publish;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\ProfileNotification as ProfileNotificationResource;
use Branch8\MarketPlaceSeller\Helper\OrderDailyNotificationHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Message\Manager;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\Marketplace\Helper\Data as HelperData;

class DownloadOrderNotification
{
    protected NotificationConfig $config;
    protected ProfileNotificationFactory $profileFactory;
    protected ProfileNotificationResource $profileResource;
    protected Publish $publish;
    protected Manager $manager;
    protected \Magento\Backend\Model\Auth\Session $authSession;
    protected RedirectFactory $redirectFactory;
    protected CollectionFactory $productCollectionFactory;
    protected HelperData $helper;
    protected OrderDailyNotificationHelper $orderDailyNotificationHelper;
    protected Session $customerSession;
    protected CustomerFactory $customerFactory;
    protected Manager $messageManager;
    protected RequestInterface $request;
    protected HttpContext $httpContext;
    protected StoreManagerInterface $storeManager;
    protected ScopeConfigInterface $scopeConfig;
    protected Resolver $store;

    /**
     * @param Session $customerSession
     * @param CollectionFactory $productCollectionFactory
     * @param HelperData $helper
     * @param OrderDailyNotificationHelper $orderDailyNotificationHelper
     * @param NotificationConfig $config
     * @param Publish $publish
     * @param ProfileNotificationResource $profileResource
     * @param CustomerFactory $customerFactory
     * @param ProfileNotificationFactory $profileFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param Manager $messageManager
     * @param RequestInterface $request
     * @param HttpContext $httpContext
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Resolver $store
     */
    public function __construct(
        Session           $customerSession,
        CollectionFactory $productCollectionFactory,
        HelperData        $helper,
        OrderDailyNotificationHelper $orderDailyNotificationHelper,
        NotificationConfig  $config,
        Publish           $publish,
        ProfileNotificationResource $profileResource,
        CustomerFactory   $customerFactory,
        ProfileNotificationFactory $profileFactory,
        RedirectFactory   $resultRedirectFactory,
        Manager           $messageManager,
        RequestInterface  $request,
        HttpContext       $httpContext,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Resolver          $store
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->profileResource = $profileResource;
        $this->profileFactory = $profileFactory;
        $this->config = $config;
        $this->publish = $publish;
        $this->redirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        $this->orderDailyNotificationHelper = $orderDailyNotificationHelper;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->httpContext = $httpContext;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->store = $store;
    }

    /**
     * @param $subject
     * @param $proceed
     * @return mixed|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundExecute($subject, $proceed)
    {
        if (!$this->config->enable()) {
            return $proceed();
        }
        $threshold = $this->config->getThreshold();
        $isPartner = $this->helper->isSeller();
        if ($isPartner == 1) {
            $sellerId = $this->helper->getCustomerId();
            $countOrderData = $this->orderDailyNotificationHelper->getOrderDataForExcel($sellerId, false);
            if ($countOrderData <= $threshold) {
                return $proceed();
            }
            $customer = $this->customerFactory->create()->load($sellerId);

            $profile = $this->profileFactory->create();
            $profile->setSellerId((int)$sellerId)
                ->setProfileType(ProfileNotification::TYPE_SELLER)
                ->setUserId((int)$customer->getId())
                ->setStoreId($this->getStoreId())
                ->setReceiverName(($this->customerSession->getCustomer()->getName()))
                ->setReceiverEmail($this->customerSession->getCustomer()->getEmail());
            $this->profileResource->save($profile);
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceOrderExportRabbitMQ', 'order_notification_download')){
                $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/branch8.order.notification.synchronization.log');
                $logger = new \Zend_Log();
                $logger->addWriter($writer);
                $logger->info(print_r('Profile public: ' . $profile->getId(), true));
            }
            $this->publish->execute($profile);
            $this->messageManager->addSuccessMessage(__('File is being prepared, once completed, it will be emailed to your inbox (%1), please pay attention to the email.',
                $this->customerSession->getCustomer()->getEmail()));
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('marketplace/order/history');
        } else {
            return $this->redirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->request->isSecure()]
            );
        }
    }

    /**
     * Get store id
     * @return int
     */
    private function getStoreId()
    {
        $localeCode = $this->store->getLocale();
        if (!$localeCode) {
            $localeCode = 'en_US';
        }
        $store = 0;
        $stores = $this->storeManager->getStores();
        //Try to get list of locale for all stores;
        foreach($stores as $store) {
            $localeCompare = $this->scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store->getStoreId());
            if ($localeCompare == $localeCode) {
                $store = $store->getId();
                break;
            }
        }
        return (int) $store;
    }

    
}
