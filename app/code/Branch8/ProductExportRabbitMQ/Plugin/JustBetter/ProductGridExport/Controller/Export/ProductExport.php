<?php

namespace Branch8\ProductExportRabbitMQ\Plugin\JustBetter\ProductGridExport\Controller\Export;

use Branch8\ProductExportRabbitMQ\Model\Config;
use Branch8\ProductExportRabbitMQ\Model\Profile;
use Branch8\ProductExportRabbitMQ\Model\ProfileFactory;
use Branch8\ProductExportRabbitMQ\Model\Queue\Publish;
use Branch8\ProductExportRabbitMQ\Model\ResourceModel\Profile as ProfileResource;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Message\Manager;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\MassAction\Filter;
use Webkul\Marketplace\Helper\Data as HelperData;

class ProductExport
{
    protected Config $config;
    protected ProfileFactory $profileFactory;
    protected ProfileResource $profileResource;
    protected Publish $publish;
    protected Manager $manager;
    protected \Magento\Backend\Model\Auth\Session $authSession;
    protected RedirectFactory $redirectFactory;
    protected CollectionFactory $productCollectionFactory;
    protected Filter $filter;
    protected HelperData $helper;
    protected Session $customerSession;
    protected CustomerFactory $customerFactory;
    protected Manager $messageManager;
    protected RequestInterface $request;
    protected HttpContext $httpContext;
    protected StoreManagerInterface $storeManager;
    protected ScopeConfigInterface $scopeConfig;
    protected Resolver $store;
    protected Registry $coreRegistry;

    /**
     * @param Filter $filter
     * @param Session $customerSession
     * @param CollectionFactory $productCollectionFactory
     * @param HelperData $helper
     * @param Config $config
     * @param Publish $publish
     * @param ProfileResource $profileResource
     * @param CustomerFactory $customerFactory
     * @param ProfileFactory $profileFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param Manager $messageManager
     * @param RequestInterface $request
     * @param HttpContext $httpContext
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Resolver $store
     * @param Registry $coreRegistry
     */
    public function __construct(
        Filter            $filter,
        Session           $customerSession,
        CollectionFactory $productCollectionFactory,
        HelperData        $helper,
        Config            $config,
        Publish           $publish,
        ProfileResource   $profileResource,
        CustomerFactory   $customerFactory,
        ProfileFactory    $profileFactory,
        RedirectFactory   $resultRedirectFactory,
        Manager           $messageManager,
        RequestInterface  $request,
        HttpContext       $httpContext,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Resolver          $store,
        Registry          $coreRegistry
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->profileResource = $profileResource;
        $this->profileFactory = $profileFactory;
        $this->config = $config;
        $this->publish = $publish;
        $this->filter = $filter;
        $this->redirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->httpContext = $httpContext;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->store = $store;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * @param $subject
     * @param $proceed
     * @return mixed|void
     * @throws \Zend_Log_Exception
     */
    public function aroundExecute($subject, $proceed)
    {
        $this->coreRegistry->register('amasty_ignore_product_filter', true);
        if (!$this->config->enable()) {
            return $proceed();
        }
        $threshold = $this->config->getThreshold();
        $isPartner = $this->helper->isSeller();
        if ($isPartner == 1) {
            try {
                $collection = $this->filter->getCollection(
                    $this->productCollectionFactory->create()
                );
                $ids = $collection->getAllIds();
                if (count($ids) <= $threshold) {
                    return $proceed();
                }
                $sellerId = $this->helper->getCustomerId();
                $customer = $this->customerFactory->create()->load($sellerId);

                if ($ids) {
                    $profile = $this->profileFactory->create();
                    $profile->setProductIds(join(",", $ids))
                        ->setProfileType(Profile::TYPE_SELLER)
                        ->setUserId((int)$customer->getId())
                        ->setStoreId($this->getStoreId())
                        ->setReceiverName(($this->customerSession->getCustomer()->getName()))
                        ->setReceiverEmail($this->customerSession->getCustomer()->getEmail());
                    $this->profileResource->save($profile);
                    if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_ProductExportRabbitMQ', 'productexport')){
                        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/branch8.product.export.synchronization.log');
                        $logger = new \Zend_Log();
                        $logger->addWriter($writer);
                        $logger->info(print_r('Profile public: ' . $profile->getId(), true));
                    }
                    $this->publish->execute($profile);
                    $this->messageManager->addSuccessMessage(__('File is being prepared, once completed, it will be emailed to your inbox (%1), please pay attention to the email.',
                        $this->customerSession->getCustomer()->getEmail()));
                } else {
                    $this->messageManager->addNotice(
                        __('There are no download files related to selected product(s).')
                    );
                }
                $redirect = $this->redirectFactory->create();
                return $redirect->setPath('marketplace/product/productlist');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $redirect = $this->redirectFactory->create();
                return $redirect->setPath('marketplace/product/productlist');
            }
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
