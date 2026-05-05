<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Controller\Adminhtml\Product;

use Magento\Store\Model\Store;
use Webkul\Marketplace\Model\Product;
use Branch8\MarketplaceProduct\Helper\ProductApproval;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect as ResultRedirect;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Eav\Processor as EavProcessor;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data;
use Webkul\Marketplace\Helper\Data as DataHelper;
use Webkul\Marketplace\Helper\Email;
use Webkul\Marketplace\Helper\Email as EmailHelper;
use Webkul\Marketplace\Helper\Notification as MarketplaceNotificationHelper;
use Webkul\Marketplace\Model\Notification;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;

/**
 * Class MassDisapprove used to mass Disapproved.
 */
class MassDealerDisapprove extends Action
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_MarketplaceProduct::MassDisapprove';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Filter
     */
    private Filter $filter;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var Processor
     */
    private Processor $productPriceIndexerProcessor;

    /**
     * @var EavProcessor
     */
    private EavProcessor $eavProcessor;

    /**
     * @var DataHelper
     */
    private DataHelper $mpHelper;

    /**
     * @var Email
     */
    private Email $mpEmailHelper;

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
     * MassDisapprove constructor.
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Processor $productPriceIndexerProcessor
     * @param EavProcessor $eavProcessor
     * @param DataHelper $mpHelper
     * @param Email $mpEmailHelper
     * @param MarketplaceNotificationHelper $mpNotificationHelper
     * @param ProductApproval $productApproval
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param MarketplaceProductManagement $marketplaceProductManagement
     */
    public function __construct(
        Context                       $context,
        LoggerInterface               $logger,
        Filter                        $filter,
        CollectionFactory             $collectionFactory,
        Processor                     $productPriceIndexerProcessor,
        EavProcessor                  $eavProcessor,
        DataHelper                    $mpHelper,
        Email                         $mpEmailHelper,
        MarketplaceNotificationHelper $mpNotificationHelper,
        ProductApproval               $productApproval,
        CategoryRepositoryInterface   $categoryRepository,
        ProductRepositoryInterface    $productRepository,
        CustomerRepositoryInterface   $customerRepository,
        MarketplaceProductManagement  $marketplaceProductManagement
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->productPriceIndexerProcessor = $productPriceIndexerProcessor;
        $this->eavProcessor = $eavProcessor;
        $this->mpHelper = $mpHelper;
        $this->mpEmailHelper = $mpEmailHelper;
        $this->mpNotificationHelper = $mpNotificationHelper;
        $this->productApproval = $productApproval;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function execute()
    {
        $productIds = [];
        $successful = $failed = 0;

        $collection = $this->filter->getCollection($this->collectionFactory->create());
        foreach ($collection as $item) {
            try {
                $productId = (int)$item->getMageproductId();
                $reviewStage = 'Dealer';
                $flag = $this->productApproval->handleDisapproveBasedOnLogEntry($productId, $reviewStage, 1);

                if ($flag) {
                    $sellerProduct = $this->marketplaceProductManagement->getByCode('mageproduct_id', $productId);
                    $sellerProduct->setData('status', Product::STATUS_DISABLED);
                    $sellerProduct->setData('seller_pending_notification', 1);
                    $this->marketplaceProductManagement->save($sellerProduct);
                }

                $item->setData('status', Status::STATUS_DISABLED);
                $item->setData('seller_pending_notification', 1);
                $item = $this->marketplaceProductManagement->save($item);

                $this->mpNotificationHelper->saveNotification(Notification::TYPE_PRODUCT, $item->getId(), $item->getMageproductId());

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

                $sellerId = (int)$item->getSellerId();
                $seller = $this->customerRepository->getById($sellerId);
                $sellerName = sprintf('%s %s', $seller->getFirstname(), $seller->getLastname());

                $adminStoreEmail = $this->mpHelper->getAdminEmailId();
                $adminEmail = $adminStoreEmail ?: $this->mpHelper->getDefaultTransEmailId();

                $emailTemplateVariables = [];
                $emailTemplateVariables['myvar1'] = $productModel->getName();
                $emailTemplateVariables['myvar2'] = $productModel->getDescription();
                $emailTemplateVariables['myvar3'] = $productModel->getPrice();
                $emailTemplateVariables['myvar4'] = $categoryName;
                $emailTemplateVariables['myvar5'] = $sellerName;
                $emailTemplateVariables['myvar6'] = 'I would like to inform you that your product has been disapproved.';
                $senderInfo = ['name' => $this->mpHelper->getAdminName(), 'email' => $adminEmail];
                $receiverInfo = ['name' => $sellerName, 'email' => $seller->getEmail()];
                $this->mpEmailHelper->sendProductUnapproveMail($emailTemplateVariables, $senderInfo, $receiverInfo);
                $this->_eventManager->dispatch('mp_disapprove_product', ['product' => $item, 'seller' => $seller]);


                $productIds[] = $productId;
                $successful++;
            } catch (\Exception $e) {
                $this->logger->error(self::LOG_PREFIX, ['exception' => $e]);
                $failed++;
            }
        }

        if ($productIds) {
            $this->productPriceIndexerProcessor->reindexList($productIds);
            $this->eavProcessor->reindexList($productIds);
            $this->mpHelper->reIndexData();
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been disapproved.', $successful));
        if ($failed) {
            $this->messageManager->addErrorMessage(__('There was an error when disapproving %1 record(s).', $failed));
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
        return $this->_authorization->isAllowed('Branch8_MarketplaceProduct::dealer_disapprove');
    }
}
