<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Controller\Adminhtml\Product;

use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceProduct\Helper\ProductApproval;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\CollectionFactory as ProductVersionCollectionFactory;
use Branch8\MarketplaceStaging\Model\Product\Source\CreatedFrom;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Backend\Model\View\Result\Redirect as ResultRedirect;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Indexer\Product\Eav\Processor as EavProcessor;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data as DataHelper;
use Webkul\Marketplace\Helper\Email as EmailHelper;
use Webkul\Marketplace\Helper\Notification as MarketplaceNotificationHelper;
use Webkul\Marketplace\Model\Notification;
use Webkul\Marketplace\Model\Product;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;

/**
 * Class MassApprove used to mass approved.
 */
class MassDealerApprove extends Action
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_MarketplaceProduct::MassApprove';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var AdminSession
     */
    private AdminSession $adminSession;

    /**
     * @var Filter
     */
    private Filter $filter;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var Processor
     */
    private Processor $productPriceIndexerProcessor;

    /**
     * @var ProductAction
     */
    private ProductAction $productAction;

    /**
     * @var EavProcessor
     */
    private EavProcessor $eavProcessor;

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
     * @var ProductApproval
     */
    private ProductApproval $productApproval;

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

    /**
     * MassApprove constructor.
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Filter $filter
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param Processor $productPriceIndexerProcessor
     * @param ProductAction $productAction
     * @param EavProcessor $eavProcessor
     * @param CustomerFactory $customerFactory
     * @param ProductFactory $productFactory
     * @param CategoryFactory $categoryFactory
     * @param DataHelper $mpHelper
     * @param EmailHelper $mpEmailHelper
     * @param MarketplaceNotificationHelper $mpNotificationHelper
     * @param ProductApproval $productApproval
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param ProductVersionCollectionFactory $productVersionCollectionFactory
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param AdminSession|null $adminSession
     */
    public function __construct(
        Context                       $context,
        LoggerInterface               $logger,
        Filter                        $filter,
        StoreManagerInterface         $storeManager,
        CollectionFactory             $collectionFactory,
        Processor                     $productPriceIndexerProcessor,
        ProductAction                 $productAction,
        EavProcessor                  $eavProcessor,
        CustomerFactory               $customerFactory,
        ProductFactory                $productFactory,
        CategoryFactory               $categoryFactory,
        DataHelper                    $mpHelper,
        EmailHelper                   $mpEmailHelper,
        MarketplaceNotificationHelper $mpNotificationHelper,
        ProductApproval               $productApproval,
        CategoryRepositoryInterface   $categoryRepository,
        ProductRepositoryInterface    $productRepository,
        CustomerRepositoryInterface   $customerRepository,
        MarketplaceProductManagement  $marketplaceProductManagement,
        ProductVersionRepositoryInterface $productVersionRepository,
        ProductVersionCollectionFactory   $productVersionCollectionFactory,
        GetProductLogEntryByProductId     $getProductLogEntryByProductId,
        AdminSession                      $adminSession = null
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->filter = $filter;
        $this->storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;
        $this->productPriceIndexerProcessor = $productPriceIndexerProcessor;
        $this->productAction = $productAction;
        $this->eavProcessor = $eavProcessor;
        $this->mpHelper = $mpHelper;
        $this->mpEmailHelper = $mpEmailHelper;
        $this->mpNotificationHelper = $mpNotificationHelper;
        $this->productApproval = $productApproval;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->productVersionRepository = $productVersionRepository;
        $this->productVersionCollectionFactory = $productVersionCollectionFactory;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
        $this->adminSession = $adminSession ?? ObjectManager::getInstance()->get(AdminSession::class);
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function execute()
    {
        //$productIds = [];
        $successful = $failed = 0;

        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $productData = [];
        foreach ($collection as $item) {
            try {
                $item->setData('dealer_approve_status', 1);
                $item = $this->marketplaceProductManagement->save($item);

                $productId = (int)$item->getMageproductId();
                $productModel = $this->productRepository->getById($productId);
                $logEntry = $this->getProductLogEntryByProductId->execute($productId);

                if (!empty($logEntry['id'])) {
                    try {
                        if ($logEntry['created_from'] == CreatedFrom::CREATED_FROM_SCHEDULE) {
                            $adminUserId = 0;
                            if ($this->adminSession->isLoggedIn()) {
                                $adminUserId = (int)$this->adminSession->getUser()->getId();
                            }
                            $productVersionList = $this->productVersionCollectionFactory->create();
                            $productVersionList->addFieldToFilter('product_id', $productId)
                                ->addFieldToFilter('main_table.status', Product::STATUS_PENDING);
                            if ($productVersionList->getSize() > 0) {
                                foreach ($productVersionList as $productVersion) {
                                    $productVersion->setReviewerId($adminUserId);
                                    $productVersion->setDealerProcessed(1);
                                    $productVersion = $this->productApproval->dealerApproveProcess($productVersion);
                                    $this->productVersionRepository->save($productVersion);
                                }
                            }
                        } else {
                            $productVersion = $this->productVersionRepository->getById((int)$logEntry['id']);
                            // if ($this->adminSession->isLoggedIn()) {
                            //     $adminUser = $this->adminSession->getUser();
                            //     $productVersion->setReviewerId((int)$adminUser->getId());
                            // }
                            $productVersion->setDealerProcessed(1);
                            $productVersion = $this->productApproval->dealerApproveProcess($productVersion);
                            $this->productVersionRepository->save($productVersion);
                        }
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                        throw new LocalizedException(__('An error occurred while denying product.'));
                    }
                }

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
                $sellerId = (int)$item->getSellerId();
                $seller = $this->customerRepository->getById($sellerId);
                $sellerName = sprintf('%s %s', $seller->getFirstname(), $seller->getLastname());
                $data['product_name'] = $productModel->getName();
                $data['product_description'] = $productModel->getDescription();
                $data['product_price'] = $productModel->getPrice();
                $data['category_name'] = $categoryName;
                $data['seller_name'] = $sellerName;
                $productData[] = $data;
                $successful++;
            } catch (\Exception $e) {

                $this->logger->error(self::LOG_PREFIX, ['exception' => $e]);
                $failed++;
            }
        }
        $adminStoreEmail = $this->mpHelper->getAdminEmailId();
        $adminEmail = $adminStoreEmail ?: $this->mpHelper->getDefaultTransEmailId();
        $emailVariables = [
            'dealer_name' => $this->mpHelper->getAdminName()
        ];
        $this->productApproval->handleEmailToAdminWhenDealerApprove($emailVariables, $productData);

        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been approved.', $successful));
        if ($failed) {
            $this->messageManager->addErrorMessage(__('There was an error when approving %1 record(s).', $failed));
        }

        /** @var ResultRedirect $resultRedirect */
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
