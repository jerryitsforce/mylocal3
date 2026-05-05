<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Controller\Adminhtml\Product;

use Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface;
use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Branch8\MarketplaceProduct\Model\Product\BuildConfigurableProduct;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\CollectionFactory as ProductVersionCollectionFactory;
use Branch8\MarketplaceStaging\Model\Product\Source\CreatedFrom;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as ProductPriceIndexerProcessor;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data as DataHelper;
use Webkul\Marketplace\Helper\Email as EmailHelper;
use Webkul\Marketplace\Helper\Notification as MarketplaceNotificationHelper;
use Webkul\Marketplace\Model\Notification;
use Webkul\Marketplace\Model\Product;
use Branch8\MarketplaceProduct\Model\Config\Source\ApprovalFlowStatus;

/**
 * Class Disapprove used to reject the product.
 */
class DealerDisapprove extends Action
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_MarketplaceProduct::Disapprove';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var AdminSession
     */
    private AdminSession $adminSession;

    /**
     * @var DataHelper
     */
    private DataHelper $mpHelper;

    /**
     * @var EmailHelper
     */
    private EmailHelper $mpEmailHelper;

    /**
     * @var MarketplaceNotificationHelper
     */
    private MarketplaceNotificationHelper $mpNotificationHelper;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ProductAction
     */
    private ProductAction $productAction;

    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var ProductPriceIndexerProcessor
     */
    private ProductPriceIndexerProcessor $productPriceIndexerProcessor;

    /**
     * @var MarketplaceProductManagement
     */
    private MarketplaceProductManagement $marketplaceProductManagement;

    /**
     * @var ProductVersionRepositoryInterface
     */
    private ProductVersionRepositoryInterface $productVersionRepository;

    /**
     * @var ProductVersionCollectionFactory
     */
    private ProductVersionCollectionFactory $productVersionCollectionFactory;

    /**
     * @var GetProductLogEntryByProductId
     */
    private GetProductLogEntryByProductId $getProductLogEntryByProductId;

    protected $productApprovalHelper;

    protected $logEntry = NULL;

    /**
     * Disapprove constructor.
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param DataHelper $mpHelper
     * @param EmailHelper $mpEmailHelper
     * @param MarketplaceNotificationHelper $mpNotificationHelper
     * @param SerializerInterface $serializer
     * @param StoreManagerInterface $storeManager
     * @param ProductAction $productAction
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductPriceIndexerProcessor $productPriceIndexerProcessor
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param ProductVersionCollectionFactory $productVersionCollectionFactory
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param \Branch8\MarketplaceProduct\Helper\ProductApproval $productApprovalHelper
     * @param AdminSession|null $adminSession
     */
    public function __construct(
        Context                           $context,
        LoggerInterface                   $logger,
        DataHelper                        $mpHelper,
        EmailHelper                       $mpEmailHelper,
        MarketplaceNotificationHelper     $mpNotificationHelper,
        SerializerInterface               $serializer,
        StoreManagerInterface             $storeManager,
        ProductAction                     $productAction,
        CategoryRepositoryInterface       $categoryRepository,
        ProductRepositoryInterface        $productRepository,
        CustomerRepositoryInterface       $customerRepository,
        ProductPriceIndexerProcessor      $productPriceIndexerProcessor,
        MarketplaceProductManagement      $marketplaceProductManagement,
        ProductVersionRepositoryInterface $productVersionRepository,
        ProductVersionCollectionFactory   $productVersionCollectionFactory,
        GetProductLogEntryByProductId     $getProductLogEntryByProductId,
        \Branch8\MarketplaceProduct\Helper\ProductApproval $productApprovalHelper,
        AdminSession                      $adminSession = null
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->mpHelper = $mpHelper;
        $this->mpEmailHelper = $mpEmailHelper;
        $this->mpNotificationHelper = $mpNotificationHelper;
        $this->serializer = $serializer;
        $this->storeManager = $storeManager;
        $this->productAction = $productAction;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->productPriceIndexerProcessor = $productPriceIndexerProcessor;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->productVersionRepository = $productVersionRepository;
        $this->productVersionCollectionFactory = $productVersionCollectionFactory;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
        $this->adminSession = $adminSession ?? ObjectManager::getInstance()->get(AdminSession::class);
        $this->productApprovalHelper = $productApprovalHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try {
            $productId = (int)$this->getRequest()->getParam('mageproduct_id');
            $this->logEntry = $this->getProductLogEntryByProductId->execute($productId);

            if((int)$this->logEntry['approval_flow_status'] > 0 && $this->logEntry['approval_flow_status'] != ApprovalFlowStatus::DISTRIBUTOR_PENDING_APPROVAL){
                $this->messageManager->addErrorMessage(__('Invalid approval status.'));
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('marketplacectrl/product');
            }

            $flag = $this->handleDisapproveBasedOnLogEntry($productId, 1);
            $sellerProduct = $this->marketplaceProductManagement->getByCode('mageproduct_id', $productId);
            $productModel = $this->productRepository->getById($productId);
            $categoryName = false;
            if ($flag) {
                $sellerProduct->setData('status', Product::STATUS_DISABLED);
                $sellerProduct->setData('seller_pending_notification', 1);
                $sellerProduct = $this->marketplaceProductManagement->save($sellerProduct);

                $this->mpNotificationHelper->saveNotification(Notification::TYPE_PRODUCT, $sellerProduct->getId(), $productId);
                foreach ($productModel->getCategoryIds() as $categoryId) {
                    $category = $this->categoryRepository->get($categoryId);
                    $categoryName = $categoryName ? ($categoryName . ',' . $category->getName()) : $category->getName();
                }
            }
            $seller = $this->customerRepository->getById((int)$sellerProduct->getSellerId());
            $notifySeller = $this->getRequest()->getParam('notify_seller');
            if ($notifySeller == 1) {
                $adminEmail = $this->mpHelper->getAdminEmailId() ?: $this->mpHelper->getDefaultTransEmailId();
                $sellerName = sprintf('%s %s', $seller->getFirstname(), $seller->getLastname());
                $emailTempVariables['myvar1'] = $sellerName;
                $emailTempVariables['myvar2'] = $this->getRequest()->getParam('product_deny_reason');
                $emailTempVariables['myvar3'] = $productModel->getName();
                $emailTempVariables['myvar4'] = $categoryName;
                $emailTempVariables['myvar5'] = $productModel->getDescription();
                $emailTempVariables['myvar6'] = $productModel->getPrice();
                $senderInfo = ['name' => $this->mpHelper->getAdminName(), 'email' => $adminEmail];
                $receiverInfo = ['name' => $sellerName, 'email' => $seller->getEmail(),];
                $this->mpEmailHelper->sendProductUnapproveMail($emailTempVariables, $senderInfo, $receiverInfo);
            }
            $this->_eventManager->dispatch('mp_disapprove_product', ['product' => $sellerProduct, 'seller' => $seller]);
            $this->messageManager->addSuccessMessage(__('Product has been Disapproved.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('marketplacectrl/product');
    }

    /**
     * Handle disapprove based on log entry.
     *
     * @param int $productId
     *
     * @return bool
     *
     * @throws LocalizedException
     */
    private function handleDisapproveBasedOnLogEntry(int $productId): bool
    {
        if($this->logEntry){
            $logEntry = $this->logEntry;
        }else{
            $logEntry = $this->getProductLogEntryByProductId->execute($productId);
        }
        $result = false;

        if (!empty($logEntry['id'])) {
            try {
                // For case created from Seller, Schedule and Import
                if ($logEntry['created_from'] == CreatedFrom::CREATED_FROM_SELLER) {
                    // START: Update child products of a configurable product in marketplace product table
                    $additionalInfo = (string)$logEntry[ProductVersionInterface::ADDITIONAL_INFORMATION];
                    $changedData = $this->serializer->unserialize($additionalInfo);
                    if (isset($changedData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS])) {
                        $beforeChildIds = $changedData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS]['before'] ?? [];
                        $afterChildIds = $changedData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS]['after'] ?? [];
                        foreach (array_diff($afterChildIds, $beforeChildIds) as $childId) {
                            try {
                                $product = $this->productRepository->getById($childId);
                                $this->productRepository->delete($product);
                            } catch (NoSuchEntityException $e) {
                                continue;
                            }
                        }
                    }
                    // END: Update child products of a configurable product in marketplace product table

                    $productVersion = $this->productVersionRepository->getById((int)$logEntry['id']);
                    $productVersion->setStatus(Product::STATUS_DISABLED);

                    $productVersion = $this->productApprovalHelper->dealerDisapproveProcess($productVersion);
                    $this->productVersionRepository->save($productVersion);
                } elseif ($logEntry['created_from'] == CreatedFrom::CREATED_FROM_SCHEDULE || $logEntry['created_from'] == CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED) {
                    
                    $productVersionList = $this->productVersionCollectionFactory->create();
                    $productVersionList->addFieldToFilter('product_id', $productId)
                        ->addFieldToFilter('main_table.status', Product::STATUS_PENDING);
                    if ($productVersionList->getSize() > 0) {
                        foreach ($productVersionList as $productVersion) {
                            $productVersion->setStatus(Product::STATUS_DISABLED);
                            $productVersion = $this->productApprovalHelper->dealerDisapproveProcess($productVersion);
                            $this->productVersionRepository->save($productVersion);
                        }
                    }
                } else {
                    $productVersion = $this->productVersionRepository->getById((int)$logEntry['id']);
                    $productVersion->setStatus(Product::STATUS_DISABLED);

                    $productVersion = $this->productApprovalHelper->dealerDisapproveProcess($productVersion);
                    $this->productVersionRepository->save($productVersion);
                }
                // END: Update child products of a configurable product in marketplace product table
                $result = true;
            } catch (\Exception $e) {
                $this->logger->error(self::LOG_PREFIX, ['exception' => $e]);
                throw new LocalizedException(__('An error occurred while disapproving product.'));
            }
        } else {
            $result = true;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Branch8_MarketplaceProduct::dealer_disapprove');
    }
}
