<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Controller\Adminhtml\Product;

use Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface;
use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Branch8\MarketplaceProduct\Model\Product\BuildConfigurableProduct;
use Branch8\MarketplaceProduct\Model\Product\SaveProductWithChanges;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Branch8\MarketplaceProduct\Model\ResourceModel\MarkPriceChange;
use Branch8\MarketplaceStaging\Model\Product\SaveProductStagingWithChanges;
use Branch8\MarketplaceStaging\Model\Product\Source\CreatedFrom;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Eav\Processor as EavProcessor;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as ProductPriceIndexerProcessor;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Store\Model\Store;
use Magento\User\Model\UserFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data as DataHelper;
use Webkul\Marketplace\Helper\Email as EmailHelper;
use Webkul\Marketplace\Helper\Notification as MarketplaceNotificationHelper;
use Webkul\Marketplace\Model\Notification;
use Webkul\Marketplace\Model\Product;
use Branch8\MarketplaceProduct\Model\Config\Source\ApprovalFlowStatus;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class Approve used to approve the product.
 */
class Approve extends Action
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_MarketplaceProduct::Approve';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var AdminSession
     */
    private AdminSession $adminSession;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @var DataHelper
     */
    private DataHelper $mpHelper;

    /**
     * @var EmailHelper
     */
    private EmailHelper $mpEmailHelper;

    /**
     * @var EavProcessor
     */
    private EavProcessor $eavProcessor;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var MarketplaceNotificationHelper
     */
    private MarketplaceNotificationHelper $mpNotificationHelper;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var QuoteResource
     */
    private QuoteResource $quoteResource;

    /**
     * @var MarkPriceChange
     */
    private MarkPriceChange $markPriceChange;

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
     * @var SaveProductWithChanges
     */
    private SaveProductWithChanges $saveProductWithChanges;

    /**
     * @var SaveProductStagingWithChanges
     */
    private SaveProductStagingWithChanges $saveProductStagingWithChanges;

    /**
     * @var ProductVersionRepositoryInterface
     */
    private ProductVersionRepositoryInterface $productVersionRepository;

    /**
     * @var GetProductLogEntryByProductId
     */
    private GetProductLogEntryByProductId $getProductLogEntryByProductId;

    /**
     * @var string
     */
    protected string $reviewerId = '';

    /**
     * @var UserFactory
     */
    private UserFactory $userFactory;

    protected $b8SubAccountHelper;

    protected $productApprovalHelper;

    protected $timezone;

    protected $logData = NULL;

    /**
     * Approve constructor.
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Registry $registry
     * @param DateTime $dateTime
     * @param DataHelper $mpHelper
     * @param EmailHelper $mpEmailHelper
     * @param MarketplaceNotificationHelper $mpNotificationHelper
     * @param SerializerInterface $serializer
     * @param EavProcessor $eavProcessor
     * @param StoreManagerInterface $storeManager
     * @param QuoteResource $quoteResource
     * @param MarkPriceChange $markPriceChange
     * @param ProductAction $productAction
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductPriceIndexerProcessor $productPriceIndexerProcessor
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param SaveProductWithChanges $saveProductWithChanges
     * @param SaveProductStagingWithChanges $saveProductStagingWithChanges
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param UserFactory $userFactory
     * @param \Branch8\MarketplaceSubAccount\Helper\Data $b8SubAccountHelper
     * @param \Branch8\MarketplaceProduct\Helper\ProductApproval $productApprovalHelper
     * @param AdminSession|null $adminSession
     */
    public function __construct(
        Context                           $context,
        LoggerInterface                   $logger,
        Registry                          $registry,
        DateTime                          $dateTime,
        DataHelper                        $mpHelper,
        EmailHelper                       $mpEmailHelper,
        MarketplaceNotificationHelper     $mpNotificationHelper,
        SerializerInterface               $serializer,
        EavProcessor                      $eavProcessor,
        StoreManagerInterface             $storeManager,
        QuoteResource                     $quoteResource,
        MarkPriceChange                   $markPriceChange,
        ProductAction                     $productAction,
        CategoryRepositoryInterface       $categoryRepository,
        ProductRepositoryInterface        $productRepository,
        CustomerRepositoryInterface       $customerRepository,
        ProductPriceIndexerProcessor      $productPriceIndexerProcessor,
        MarketplaceProductManagement      $marketplaceProductManagement,
        SaveProductWithChanges            $saveProductWithChanges,
        SaveProductStagingWithChanges     $saveProductStagingWithChanges,
        ProductVersionRepositoryInterface $productVersionRepository,
        GetProductLogEntryByProductId     $getProductLogEntryByProductId,
        UserFactory                       $userFactory,
        \Branch8\MarketplaceSubAccount\Helper\Data $b8SubAccountHelper,
        \Branch8\MarketplaceProduct\Helper\ProductApproval $productApprovalHelper,
        TimezoneInterface                 $timezone,
        AdminSession                      $adminSession = null
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->registry = $registry;
        $this->mpHelper = $mpHelper;
        $this->dateTime = $dateTime;
        $this->mpEmailHelper = $mpEmailHelper;
        $this->mpNotificationHelper = $mpNotificationHelper;
        $this->serializer = $serializer;
        $this->eavProcessor = $eavProcessor;
        $this->storeManager = $storeManager;
        $this->quoteResource = $quoteResource;
        $this->markPriceChange = $markPriceChange;
        $this->productAction = $productAction;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->productPriceIndexerProcessor = $productPriceIndexerProcessor;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->saveProductWithChanges = $saveProductWithChanges;
        $this->saveProductStagingWithChanges = $saveProductStagingWithChanges;
        $this->productVersionRepository = $productVersionRepository;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
        $this->userFactory = $userFactory;
        $this->adminSession = $adminSession ?? ObjectManager::getInstance()->get(AdminSession::class);
        $this->b8SubAccountHelper = $b8SubAccountHelper;
        $this->productApprovalHelper = $productApprovalHelper;
        $this->timezone = $timezone;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $productId = (int)$this->getRequest()->getParam('mageproduct_id');
        $status = $this->getRequest()->getParam('status');
        try {
            $sellerProduct = $this->marketplaceProductManagement->getByCode('mageproduct_id', $productId);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Product not found.'));
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('marketplacectrl/product');
        }
        /**
         * Check the gross profit and navigate process
         */
        $this->logData = $this->getProductLogEntryByProductId->execute($productId);
        $allowFlowStatus = [ApprovalFlowStatus::DISTRIBUTOR_PENDING_APPROVAL, ApprovalFlowStatus::CURATOR_PENDING_APPROVAL, ApprovalFlowStatus::MANAGER_PENDING_APPROVAL];
        $approvalFlowStatus = (int)$this->logData['approval_flow_status'];
        $gridNamespace = $this->getRequest()->getParam('grid_namespace');
        if(
            ($approvalFlowStatus > 0 && !in_array($approvalFlowStatus, $allowFlowStatus))
            || ($gridNamespace == 'marketplacectrl_products_list' && $approvalFlowStatus > 0 && !in_array($approvalFlowStatus, [ApprovalFlowStatus::DISTRIBUTOR_PENDING_APPROVAL, ApprovalFlowStatus::CURATOR_PENDING_APPROVAL]))
            || ($gridNamespace == 'marketplacectrl_manager_products_list' && $approvalFlowStatus > 0 && $approvalFlowStatus != ApprovalFlowStatus::MANAGER_PENDING_APPROVAL)
            ){
            $this->messageManager->addErrorMessage(__('Invalid approval status.'));
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            if($gridNamespace == 'marketplacectrl_products_list'){
                return $resultRedirect->setPath('marketplacectrl/product/index');
            }
            if($gridNamespace == 'marketplacectrl_manager_products_list'){
                return $resultRedirect->setPath('marketplacectrl/managerProduct/index');
            }
            return $resultRedirect->setPath('admin/dashboard/index');
        }
        /** if commission_percent < 0, only change status, if not, approve and apply data */
        if((float)$this->logData['commission_percent'] < 0 || $this->productApprovalHelper->isVariantionNegative($this->logData)){
            if($gridNamespace == 'marketplacectrl_products_list' && in_array($this->logData['approval_flow_status'], [ApprovalFlowStatus::CURATOR_PENDING_APPROVAL, ApprovalFlowStatus::DISTRIBUTOR_PENDING_APPROVAL])){
                /**
                 * For Curator pending, only change status to Manager pending
                 */
                $this->productApprovalHelper->curatorApproveProcessNegativeGrossProfit($this->logData['id']);
                $this->messageManager->addSuccessMessage(__('Product has been approved.')); 
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('marketplacectrl/product');  
            }else if($gridNamespace == 'marketplacectrl_products_list' && in_array($this->logData['approval_flow_status'], 
                [ApprovalFlowStatus::APPROVAL_GRANTED_FINAL_APPROVE, ApprovalFlowStatus::MANAGER_PENDING_APPROVAL, ApprovalFlowStatus::APPROVAL_REJECTED])){
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('marketplacectrl/product');
            }
            
        }

        try {
            //            $sellerProduct->setData('status', Product::STATUS_ENABLED);
            $sellerProduct->setData('seller_pending_notification', 1);
            $sellerProduct->setData('new_need_approve', 0);
            $sellerProduct->setData('is_approved', 1);

            $seller = $this->customerRepository->getById((int)$sellerProduct->getSellerId());
            $sellerId = (int)$seller->getId();
            list($userUpdated, $isNewProduct) = $this->handleApprovalBasedOnLogEntry($productId, $sellerId, $gridNamespace);
            if ($isNewProduct) {
                $sellerProduct->setData('first_enabled_date', $this->dateTime->gmtDate());
            }
            $sellerProduct = $this->marketplaceProductManagement->save($sellerProduct);

            /*$productIds = [$productId];
            $allStores = $this->storeManager->getStores();
            foreach ($allStores as $store) {
                $this->productAction->updateAttributes($productIds, ['status' => Status::STATUS_ENABLED], $store->getId());
            }
            $this->productAction->updateAttributes($productIds, ['status' => Status::STATUS_ENABLED], Store::DEFAULT_STORE_ID);
            $this->productPriceIndexerProcessor->reindexList($productIds);
            $this->eavProcessor->reindexList($productIds);*/

            $type = Notification::TYPE_PRODUCT;
            $this->mpNotificationHelper->saveNotification($type, $sellerProduct->getId(), $productId);

            $productModel = $this->productRepository->getById($productId);

            $categoryIds = $productModel->getCategoryIds();
            $categoryName = false;
            foreach ($categoryIds as $categoryId) {
                $category = $this->categoryRepository->get($categoryId);
                if (!$categoryName) {
                    $categoryName = $category->getName();
                } else {
                    $categoryName = $categoryName . ',' . $category->getName();
                }
            }

            $adminStoreEmail = $this->mpHelper->getAdminEmailId();
            $adminEmail = $adminStoreEmail ?: $this->mpHelper->getDefaultTransEmailId();
            $adminName = $this->mpHelper->getAdminName();
            // $dealerInfo = [];
            // if ($reviewerId) {
            //     $dealer = $this->userFactory->create()->load($reviewerId);
            //     $dealerName = $dealer->getName();
            //     $dealerEmail = $dealer->getEmail();
            //     $dealerInfo = ['name' => $dealerName, 'email' => $dealerEmail];
            // }

            $sellerName = sprintf('%s %s', $seller->getFirstname(), $seller->getLastname());

            $emailTemplateVariables = [];
            $emailTemplateVariables['myvar1'] = $productModel->getName();
            $emailTemplateVariables['myvar2'] = $productModel->getDescription();
            $emailTemplateVariables['myvar3'] = $productModel->getPrice();
            $emailTemplateVariables['myvar4'] = $categoryName;
            $emailTemplateVariables['myvar5'] = $sellerName;
            $emailTemplateVariables['myvar6'] = 'I would like to inform you that your product has been approved.';
            $senderInfo = ['name' => $adminName, 'email' => $adminEmail];

            $receiverInfo = ['name' => $sellerName, 'email' => $seller->getEmail()];
            $this->mpEmailHelper->sendProductStatusMail($emailTemplateVariables, $senderInfo, $receiverInfo);
            /**
             * They should probably only be updated after the dealer / curator / manager completes the review
             * Also, the correct behavior should be that when the review is rejected, the email should be sent to the seller, not to the reviewer.
             * Discussed with Karen
             */
            // if ($reviewerId) {
            //     $this->mpEmailHelper->sendProductStatusMail($emailTemplateVariables, $senderInfo, $dealerInfo);
            // }
            /**
             * Send mail to Sub accounts
             */
            $this->b8SubAccountHelper->sendProductStatusMailToSubAccount($sellerId, $emailTemplateVariables, $senderInfo);

            $this->_eventManager->dispatch('mp_approve_product', ['product' => $sellerProduct, 'seller' => $seller, 'user_updated' => $userUpdated]);

            $this->mpHelper->reIndexData();
            $this->messageManager->addSuccessMessage(__('Product has been approved.'));
        } catch (\Exception $e) {
            $sellerProduct->setData('status', Product::STATUS_PENDING);
            $sellerProduct->setData('seller_pending_notification', 0);
            $sellerProduct->setData('is_approved', 0);
            $this->marketplaceProductManagement->save($sellerProduct);
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if($gridNamespace == 'marketplacectrl_products_list'){
            return $resultRedirect->setPath('marketplacectrl/product');
        }else if($gridNamespace == 'marketplacectrl_manager_products_list'){
            return $resultRedirect->setPath('marketplacectrl/managerProduct');
        }else{
            return $resultRedirect->setPath('admin/dashboard/index');
        }

    }

    /**
     * Handle approval based on log entry.
     *
     * @param int $productId
     * @param int $sellerId
     *
     * @return mixed
     *
     * @throws LocalizedException
     */
    private function handleApprovalBasedOnLogEntry(int $productId, int $sellerId, string $gridNamespace): mixed
    {
        $userUpdated = '';
        $flagCheck = false;
        $logEntry = $this->getProductLogEntryByProductId->execute($productId);
        $logEntryId = false;
        if (isset($logEntry['id']) && $logEntry['id']) {
            $flagCheck = true;
            $logEntryId = $logEntry['id'];
            if (isset($logEntry['is_new_product']) && $logEntry['is_new_product']) {
                $allLogEntry = $this->getProductLogEntryByProductId->executeAll($productId, true);
                if (count($allLogEntry) < 1) {
                    $flagCheck = false;
                }
            }
        }
        if (isset($logEntry['user_updated']) && $logEntry['user_updated']) {
            $userUpdated = $logEntry['user_updated'];
        }

        if ($flagCheck) {
            try {
                $catalogProduct = $this->productRepository->getById($productId);
                $this->registry->unregister('current_product_links_before_approve');
                $this->registry->register('current_product_links_before_approve', $catalogProduct->getProductLinks() ?: false);
                if ($logEntry['created_from'] != CreatedFrom::CREATED_FROM_SCHEDULE && $logEntry['created_from'] != CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED) {
                    $this->saveProductWithChanges->execute($catalogProduct, $sellerId, null, true);

                    $additionalInfo = $logEntry[ProductVersionInterface::ADDITIONAL_INFORMATION];
                    $changedData = $this->serializer->unserialize((string)$additionalInfo);

                    // START: Update child products of a configurable product in marketplace product table
                    if (isset($changedData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS])) {
                        $beforeChildIds = $changedData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS]['before'] ?? [];
                        $afterChildIds = $changedData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS]['after'] ?? [];
                        foreach (array_diff($afterChildIds, $beforeChildIds) as $childId) {
                            try {
                                $childProduct = $this->marketplaceProductManagement->getByCode('mageproduct_id', $childId);
                                $childProduct->setData('status', Product::STATUS_ENABLED);
                                $childProduct->setData('updated_at', $this->dateTime->gmtDate());
                                $this->marketplaceProductManagement->save($childProduct);
                            } catch (NoSuchEntityException $e) {
                                continue;
                            }
                        }
                    }
                    // END: Update child products of a configurable product in marketplace product table.

                    // START: Mark the customer's cart as containing products with modified price
                    if (isset($changedData[ProductInterface::PRICE])) {
                        $this->quoteResource->markQuotesRecollect($productId);
                        $this->markPriceChange->execute([$productId]);
                    }
                    // END: Mark the customer's cart as containing products with modified price
                    if (isset($logEntry['is_new_product']) && $logEntry['is_new_product']) {
                        $catalogProduct = $this->productRepository->getById($productId);
                        $catalogProduct->setStatus(Status::STATUS_ENABLED);
                        $catalogProduct->setStoreId(Store::DEFAULT_STORE_ID);
                        $this->productRepository->save($catalogProduct);
                        $isNewProduct = true;
                    }

                    $this->updateProductVersion($logEntryId, $gridNamespace);
                } else {
                    $isImport = false;
                    if ($logEntry['created_from'] == CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED) {
                        $isImport = true;
                    }
                    $adminUserId = 0;
                    if ($this->adminSession->isLoggedIn()) {
                        $adminUserId = (int)$this->adminSession->getUser()->getId();
                    }
                    $reviewerStage = '';
                    $flowStatus = 1;
                    if($gridNamespace == 'marketplacectrl_products_list'){
                        $reviewerStage = 'Curator';
                        $flowStatus = ApprovalFlowStatus::APPROVAL_GRANTED_FINAL_APPROVE;
                    }else if($gridNamespace == 'marketplacectrl_manager_products_list'){
                        $reviewerStage = 'Manager';
                        $flowStatus = ApprovalFlowStatus::APPROVAL_GRANTED_FINAL_APPROVE;
                    }

                    $this->saveProductStagingWithChanges->execute($productId, Product::STATUS_ENABLED, $adminUserId, $flowStatus, $reviewerStage, $isImport);
                    try {
                        $sellerProduct = $this->marketplaceProductManagement->getByCode('mageproduct_id', $productId);
                        if (Product::STATUS_PENDING == $sellerProduct->getStatus()) {
                            $sellerProduct->setStatus(Product::STATUS_ENABLED);
                            $this->marketplaceProductManagement->save($sellerProduct);
                        }
                    } catch (NoSuchEntityException $e) {
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error(self::LOG_PREFIX, ['exception' => $e]);
                throw new LocalizedException(__('An error occurred while approving product.' . $e->getMessage()));
            }
        } else {
            $catalogProduct = $this->productRepository->getById($productId);
            $catalogProduct->setStatus(Status::STATUS_ENABLED);
            $catalogProduct->setStoreId(Store::DEFAULT_STORE_ID);
            $this->productRepository->save($catalogProduct);
            if (isset($logEntry['status']) && $logEntry['status'] == Product::STATUS_PENDING) {
                $this->updateProductVersion($logEntryId, $gridNamespace);
            }
            if (isset($logEntry['is_new_product']) && $logEntry['is_new_product']) {
                $isNewProduct = true;
            }
        }
        return [$userUpdated, $isNewProduct ?? false];
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Branch8_MarketplaceProduct::approve') || $this->_authorization->isAllowed('Branch8_MarketplaceProduct::manager_product_approval');
    }

    protected function updateProductVersion($logEntryId, $gridNamespace, $status = Product::STATUS_ENABLED)
    {
        if (!$logEntryId) {
            return;
        }
        $productVersion = $this->productVersionRepository->getById((int)$logEntryId);
        $productVersion->setStatus($status);

        $userStage = '';
        if($gridNamespace == 'marketplacectrl_products_list'){
            $userStage = 'Curator';
        }else if($gridNamespace == 'marketplacectrl_manager_products_list'){
            $userStage = 'Manager';
        }

        if ($this->adminSession->isLoggedIn()) {
            $adminUser = $this->adminSession->getUser();
            $productVersion->setReviewerId((int)$adminUser->getId());

            $userLogInfor = [
                'user_id' => $adminUser->getId(),
                'user_name' => $adminUser->getUsername(),
                'user_role' => $adminUser->getRole()->getRoleName(),
                'user_stage' => $userStage,
                'status_updated' => 'Approved',
                'created_at' => $this->timezone->date()->format('M d, Y h:i:s A')
            ];
            $approvalLog = $productVersion->getApprovalLog();
            if($approvalLog){
                $newApprovalLog = json_decode($approvalLog, true);
            }
            $newApprovalLog[] = $userLogInfor;
            $newApprovalLogStr = json_encode($newApprovalLog);
            $productVersion->setApprovalLog($newApprovalLogStr);
            $productVersion->setApprovalFlowStatus(ApprovalFlowStatus::APPROVAL_GRANTED_FINAL_APPROVE);
        }
        $this->productVersionRepository->save($productVersion);
    }
}
