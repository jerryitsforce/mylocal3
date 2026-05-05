<?php

declare(strict_types=1);

namespace Branch8\Catalog\Observer;

use Branch8\Catalog\Model\ResourceModel\GetProductNameStatusByIds;
use Branch8\Catalog\Model\ResourceModel\ProductChangeHistory as HistoryResource;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\Store;
use Magento\User\Model\UserFactory;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\SellerSubAccount\Helper\Data as SellerSubAccountHelper;

class ProductStatusAttributeUpdateBeforeObserver implements ObserverInterface
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_Catalog::ProductStatusAttributeUpdateBeforeObserver';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var AuthSession
     */
    private AuthSession $authSession;

    /**
     * @var MarketplaceHelper
     */
    private MarketplaceHelper $marketplaceHelper;

    /**
     * @var SellerSubAccountHelper
     */
    private SellerSubAccountHelper $sellerSubAccountHelper;

    /**
     * @var HistoryResource
     */
    private HistoryResource $historyResource;

    /**
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $productCollectionFactory;

    /**
     * @var UserFactory
     */
    protected UserFactory $userFactory;

    /**
     * @var CustomerFactory
     */
    protected CustomerFactory $customerModel;

    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var ProductAction
     */
    private ProductAction $productAction;

    /**
     * @var GetProductNameStatusByIds
     */
    private GetProductNameStatusByIds $getProductNameStatusByIds;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param UserContextInterface $userContext
     * @param AuthSession $authSession
     * @param MarketplaceHelper $marketplaceHelper
     * @param SellerSubAccountHelper $sellerSubAccountHelper
     * @param HistoryResource $historyResource
     * @param ProductCollectionFactory $productCollectionFactory
     * @param UserFactory $userFactory
     * @param CustomerFactory $customerModel
     * @param ProductAction $productAction
     * @param GetProductNameStatusByIds $getProductStatusByIds
     */
    public function __construct(
        LoggerInterface           $logger,
        UserContextInterface      $userContext,
        AuthSession               $authSession,
        MarketplaceHelper         $marketplaceHelper,
        SellerSubAccountHelper    $sellerSubAccountHelper,
        HistoryResource           $historyResource,
        ProductCollectionFactory  $productCollectionFactory,
        UserFactory               $userFactory,
        CustomerFactory           $customerModel,
        ProductAction             $productAction,
        GetProductNameStatusByIds $getProductNameStatusByIds,
    ) {
        $this->logger = $logger;
        $this->userContext = $userContext;
        $this->authSession = $authSession;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->sellerSubAccountHelper = $sellerSubAccountHelper;
        $this->historyResource = $historyResource;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->userFactory = $userFactory;
        $this->customerModel = $customerModel;
        $this->productAction = $productAction;
        $this->getProductNameStatusByIds = $getProductNameStatusByIds;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $attrData = $event->getAttributesData();
        $productIds = $event->getProductIds();

        $bulkData = null;
        if (isset($attrData['updated_in_bulk'])) {
            $bulkData = $attrData['updated_in_bulk'];
            unset($attrData['updated_in_bulk']);
            $observer->getEvent()->setAttributesData($attrData);
            if ($bulkData) {
                $bulkData = $this->prepareBulkData($bulkData);
            }
        }

        if (empty($productIds) || empty($attrData) || array_key_exists('admin_user_updated', $attrData)) {
            return;
        }
        $selectAttributes = [];
        foreach ($attrData as $key => $value) {
            $selectAttributes[] = $key;
        }

        if (!isset($attrData[ProductInterface::STATUS])) {
            $products = $this->productCollectionFactory->create()
                ->addIdFilter($productIds)
                ->addAttributeToSelect($selectAttributes);
            $user = $this->getUpdatedByUser();
            $key = key($user);
            $sellerName = '';
            if ($key === 'admin') {
                $sellerName = $this->authSession->getUser()?->getUserName();
            } elseif ($key === 'seller') {
                $customer = $this->customerModel->create()->load($user[$key]);
                $sellerName = $customer->getData('prefix') . " " . $customer->getFirstname() . ' ' . $customer->getLastname();
            }
            $listProductIds = [];
            foreach ($products as $product) {
                $productId = $this->processUserChange($attrData, $product, $sellerName);
                if ($productId) {
                    $listProductIds[] = $productId;
                }
            }
            if (!empty($listProductIds)) {
                $this->productAction->updateAttributes(
                    $listProductIds,
                    ['admin_user_updated' => $sellerName],
                    Store::DEFAULT_STORE_ID
                );
            }
            return;
        }

        $newStatus = (int)$attrData[ProductInterface::STATUS];
        $products = $this->productCollectionFactory->create()
            ->addIdFilter($productIds)
            ->addAttributeToSelect($selectAttributes);
        $user = $this->getUpdatedByUser();
        $key = key($user);
        $sellerName = '';
        if ($key === 'admin') {
            $sellerName = $this->userFactory->create()->load($user[$key])->getUserName();
        } elseif ($key === 'seller') {
            $customer = $this->customerModel->create()->load($user[$key]);
            $sellerName = $customer->getData('prefix') . " " . $customer->getFirstname() . ' ' . $customer->getLastname();
        }
        $listProductIds = [];
        foreach ($products as $product) {
            $productId = $this->processUserChange($attrData, $product, $sellerName);
            if ($productId) {
                $listProductIds[] = $productId;
            }
            $currentStatus = (int)$product->getStatus();
            if ($currentStatus === $newStatus) {
                continue;
            }
            $data = [
                'product_id' => $product->getId(),
                'changed_field' => ProductAttributeInterface::CODE_STATUS,
                'before_value' => $currentStatus,
                'after_value' => $newStatus,
                'changed_by' => key($user),
                'changed_by_id' => reset($user),
                'trace_log' => json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
            ];

            if ($bulkData) {
                $data['updated_in_bulk'] = json_encode($bulkData);
            }

            try {
                $this->historyResource->insert($data);
            } catch (\Exception $e) {
                $this->logger->error(self::LOG_PREFIX, ['exception' => $e->getMessage()]);
                continue;
            }
        }
        if (!empty($listProductIds)) {
            $this->productAction->updateAttributes(
                $listProductIds,
                ['admin_user_updated' => $sellerName],
                Store::DEFAULT_STORE_ID
            );
        }
    }

    /**
     * Get the ID of the user who updated the data.
     *
     * @return array
     */
    private function getUpdatedByUser(): array
    {
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_ADMIN) {
            $userId = $this->userContext->getUserId();
            if ($userId) {
                return ['admin' => (int)$userId];
            }
        }
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
            $userId = $this->userContext->getUserId();
            if ($userId) {
                return ['seller' => (int)$userId];
            }
        }

        $userId = $this->authSession->getUser()?->getId();
        if (!empty($userId)) {
            return ['admin' => (int)$userId];
        }

        $isPartner = $this->marketplaceHelper->isSeller();
        if ($isPartner == 1) {
            $sellerId = $this->sellerSubAccountHelper->getCustomerId();
            if (!$sellerId) {
                $sellerId = $this->marketplaceHelper->getCustomerId();
            }
            return ['seller' => (int)$sellerId];
        }

        return ['system' => null];
    }

    /**
     * Process user change.
     *
     * @param array $attrData
     * @param $product
     * @param $user
     * @return mixed
     */
    private function processUserChange(array $attrData, $product, $sellerName): mixed
    {
        if (!$sellerName) return null;
        $hasDataChange = false;
        foreach ($attrData as $key => $value) {
            if ((is_float($product->getData($key)) || is_float($value))
                && (float)$product->getData($key) !== (float)$value) {
                $hasDataChange = true;
            } elseif ($product->getData($key) !== $value) {
                $hasDataChange = true;
                break;
            }
        }
        if ($hasDataChange) {
            $lastUpdatedUser = $product->getData('admin_user_updated');
            if ($sellerName != $lastUpdatedUser) {
                // Set the custom attribute value
                return $product->getId();
            }
        }
        return null;
    }

    /**
     * Prepare bulk data.
     *
     * @param array $data
     *
     * @return array|false
     */
    private function prepareBulkData(array $data): array|false
    {
        if (empty($data['product_ids'])) {
            return false;
        }

        $action = $data['action'];
        $targetStatus = false;
        if ($action === 'MassEnable') {
            $targetStatus = Status::STATUS_ENABLED;
        } elseif ($action === 'MassDisable') {
            $targetStatus = Status::STATUS_DISABLED;
        }

        if (false === $targetStatus) {
            return false;
        }

        $productIds = $data['product_ids'];
        $productData = $this->getProductNameStatusByIds->execute($productIds);
        $productStatuses = array_column($productData, 'status', 'product_id');
        $productLabels = array_column($productData, 'product_label', 'product_id');

        $productIdUpdated = array_filter($productStatuses, function ($status) use ($targetStatus) {
            return $status != $targetStatus;
        });

        return [
            'action' => $action,
            'selected' => ['qty' => count($productIds), 'items' => array_values($productLabels)],
            'updated' => ['qty' => count($productIdUpdated), 'items' => array_intersect_key($productLabels, $productIdUpdated)]
        ];
    }
}
