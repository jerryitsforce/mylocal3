<?php
declare(strict_types=1);

namespace Branch8\ProductExportRabbitMQ\Plugin\JustBetter\ProductGridExport\Controller\Adminhtml\Export;

use Branch8\ProductExportRabbitMQ\Model\Config;
use Branch8\ProductExportRabbitMQ\Model\Profile;
use Branch8\ProductExportRabbitMQ\Model\ProfileFactory;
use Branch8\ProductExportRabbitMQ\Model\Queue\Publish;
use Branch8\ProductExportRabbitMQ\Model\ResourceModel\Profile as ProfileResource;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Message\Manager;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\MassAction\Filter;

class ProductExport
{
    protected Config $config;
    protected ProfileFactory $profileFactory;
    protected ProfileResource $profileResource;
    protected Publish $publish;
    protected Manager $messageManager;
    protected Session $authSession;
    protected RedirectFactory $redirectFactory;
    protected Filter $filter;
    protected CollectionFactory $productCollectionFactory;
    protected StoreManagerInterface $storeManager;
    protected ScopeConfigInterface $scopeConfig;
    protected Resolver $store;

    /**
     * @param Config $config
     * @param Publish $publish
     * @param ProfileResource $profileResource
     * @param ProfileFactory $profileFactory
     * @param Manager $messageManager
     * @param Session $authSession
     * @param CollectionFactory $productCollectionFactory
     * @param Filter $filter
     * @param RedirectFactory $resultRedirectFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Resolver $store
     */
    public function __construct(
        Config            $config,
        Publish           $publish,
        ProfileResource   $profileResource,
        ProfileFactory    $profileFactory,
        Manager           $messageManager,
        Session           $authSession,
        CollectionFactory $productCollectionFactory,
        Filter            $filter,
        RedirectFactory   $resultRedirectFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Resolver          $store
    ){
        $this->profileResource = $profileResource;
        $this->profileFactory = $profileFactory;
        $this->config = $config;
        $this->messageManager = $messageManager;
        $this->publish = $publish;
        $this->authSession = $authSession;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->filter = $filter;
        $this->redirectFactory = $resultRedirectFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->store = $store;
    }

    /**
     * @param $subject
     * @param $proceed
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundExecute($subject, $proceed)
    {
        if (!$this->config->enable()) {
            return $proceed();
        }
        try {
            $collection = $this->filter->getCollection($this->productCollectionFactory->create());
            $ids = array_unique($collection->getAllIds());
            $threshold = $this->config->getThreshold();
            if (count($ids) <= $threshold) {
                return $proceed();
            }
            $user = $this->authSession->getUser();
            $profile = $this->profileFactory->create();
            $profile->setProductIds(join(",", $ids))
                ->setProfileType(Profile::TYPE_ADMIN)
                ->setUserId((int)$user->getId())
                ->setStoreId($this->getStoreId())
                ->setReceiverName($user->getName())
                ->setReceiverEmail($user->getEmail());
            $this->profileResource->save($profile);
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_ProductExportRabbitMQ', 'productexport')){
                $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/branch8.product.export.synchronization.log');
                $logger = new \Zend_Log();
                $logger->addWriter($writer);
                $logger->info(print_r('Profile public: ' . $profile->getId(), true));
            }
            $this->publish->execute($profile, true);
            $this->messageManager->addSuccessMessage(__('File is being prepared, once completed, it will be emailed to your inbox (%1), please pay attention to the email.', $user->getEmail()));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        $redirect = $this->redirectFactory->create();
        return $redirect->setPath('catalog/product/', ['_current' => true]);
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
