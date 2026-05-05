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
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\CollectionFactory as ProductVersionCollectionFactory;
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
use Magento\Framework\Serialize\SerializerInterface;
use \Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data as DataHelper;
use Webkul\Marketplace\Helper\Email as EmailHelper;
use Webkul\Marketplace\Helper\Notification as MarketplaceNotificationHelper;
use Webkul\Marketplace\Model\Product;
use Branch8\MarketplaceProduct\Helper\ProductApproval;
use Branch8\MarketplaceProduct\Model\Config\Source\ApprovalFlowStatus;
/**
 * Class Approve used to approve the product.
 */
class DealerApprove extends Action
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
     * @var AdminSession
     */
    private AdminSession $adminSession;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezoneInterface;

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
     * @var ProductVersionCollectionFactory
     */
    private ProductVersionCollectionFactory $productVersionCollectionFactory;

    /**
     * @var GetProductLogEntryByProductId
     */
    private GetProductLogEntryByProductId $getProductLogEntryByProductId;

    private ProductApproval $productApproval;

    /**
     * Approve constructor.
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param TimezoneInterface $timezoneInterface
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
     * @param ProductVersionCollectionFactory $productVersionCollectionFactory
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param AdminSession|null $adminSession
     */
    public function __construct(
        Context                           $context,
        LoggerInterface                   $logger,
        TimezoneInterface                 $timezoneInterface,
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
        ProductVersionCollectionFactory   $productVersionCollectionFactory,
        GetProductLogEntryByProductId     $getProductLogEntryByProductId,
        ProductApproval                   $productApproval,
        AdminSession                      $adminSession = null
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->mpHelper = $mpHelper;
        $this->timezoneInterface = $timezoneInterface;
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
        $this->productVersionCollectionFactory = $productVersionCollectionFactory;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
        $this->productApproval = $productApproval;
        $this->adminSession = $adminSession ?? ObjectManager::getInstance()->get(AdminSession::class);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $productId = (int)$this->getRequest()->getParam('mageproduct_id');
        try {
            $sellerProduct = $this->marketplaceProductManagement->getByCode('mageproduct_id', $productId);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Product not found.'));
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('marketplacectrl/product');
        }
        try {
            
            $logEntry = $this->getProductLogEntryByProductId->execute($productId);
            if((int)$logEntry['approval_flow_status'] > 0 && $logEntry['approval_flow_status'] != ApprovalFlowStatus::DISTRIBUTOR_PENDING_APPROVAL){
                $this->messageManager->addErrorMessage(__('Invalid approval status.'));
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('marketplacectrl/product');
            }

            $sellerProduct->setData('dealer_approve_status', 1);
            $sellerProduct = $this->marketplaceProductManagement->save($sellerProduct);

            if (!empty($logEntry['id'])) {
                try {
                    if ($logEntry['created_from'] == CreatedFrom::CREATED_FROM_SCHEDULE || $logEntry['created_from'] == CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED) {
                        
                        $productVersionList = $this->productVersionCollectionFactory->create();
                        $productVersionList->addFieldToFilter('product_id', $productId)
                            ->addFieldToFilter('main_table.status', Product::STATUS_PENDING);
                        if ($productVersionList->getSize() > 0) {
                            foreach ($productVersionList as $productVersion) {
                                $productVersion = $this->productApproval->dealerApproveProcess($productVersion);
                                $this->productVersionRepository->save($productVersion);
                            }
                        }
                    } else {
                        $productVersion = $this->productVersionRepository->getById((int)$logEntry['id']);
                        $productVersion = $this->productApproval->dealerApproveProcess($productVersion);
                        $this->productVersionRepository->save($productVersion);
                    }
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                    throw new LocalizedException(__('An error occurred while denying product.'));
                }
            }

            $seller = $this->customerRepository->getById((int)$sellerProduct->getSellerId());

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

            $sellerName = sprintf('%s %s', $seller->getFirstname(), $seller->getLastname());

            $data = [];
            $data['product_name'] = $productModel->getName();
            $data['sku'] = $productModel->getSku();
//            $data['product_description'] = strip_tags($productModel->getDescription());
            $data['seller_name'] = $sellerName;
            $data['apply_time'] = $this->timezoneInterface->date($productModel->getCreatedAt())->format('Y-m-d H:i:s');
            $emailVariables = [
                'dealer_name' => $this->mpHelper->getAdminName()
            ];
            $this->productApproval->handleEmailToAdminWhenDealerApprove($emailVariables, [$data]);

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
        return $resultRedirect->setPath('marketplacectrl/product');
    }


    /**
     * @inheritDoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Branch8_MarketplaceProduct::dealer_approve');
    }
}
