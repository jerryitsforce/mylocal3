<?php

namespace Branch8\Rma\Helper;

use Branch8\CTBC\Model\OrderManagement;
use Branch8\Customer\Model\GetCustomerNickname;
use Branch8\GA4\Model\ProductHelper;
use Branch8\Rma\Helper\Config\StatusLabel;
use Branch8\Rma\Helper\Status as RmaStatus;
use Branch8\Sales\Helper\Order\UpdateOrderStatus as ItemStatus;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Branch8\Rma\Model\SenderType;
use Magento\Customer\Model\Customer;
use Magento\Sales\Model\Order\Item;
use Webkul\MpRmaSystem\Model\DetailsFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Branch8\Sales\Helper\Data as DataHelper;
use Magento\Framework\Url;
use Magento\Sales\Model\Order;

/**
 * RMA Record Helper
 * 
 * Handles creation and management of RMA (Return Merchandise Authorization) records
 * 
 * @package Branch8\Rma\Helper
 */
class RmaRecord
{
    /**
     * Log option value for this helper.
     */
    private const LOG_OPTION = 'RmaRecord';
    /**
     * Maximum phone number length
     */
    private const MAX_PHONE_LENGTH = 10;

    /**
     * Default RMA reason label
     */
    private const DEFAULT_RMA_REASON = '廠退資料';

    /**
     * Date format for order display
     */
    private const ORDER_DATE_FORMAT = 'Y/m/d H:i';

    /**
     * Date format for database
     */
    private const DATABASE_DATE_FORMAT = 'Y-m-d H:i:s';

    /** @var Data */
    protected $mpRmaHelper;

    /** @var DetailsFactory */
    protected $details;

    /** @var MessageManagerInterface */
    protected $messageManager;

    /** @var StatusLabel */
    protected $statusLabel;

    /** @var Customer */
    protected $customer;

    /** @var ItemStatus */
    protected $itemStatus;

    /** @var RmaStatus */
    protected $status;

    /** @var string|null */
    protected $flowStatus;

    /** @var Item */
    protected $orderItem;

    /** @var string|null */
    protected $sender;

    /** @var TimezoneInterface */
    protected $localeDate;

    /** @var int|null */
    protected $rmaDetailRecordId;

    /** @var array|null */
    protected $rmaDetailData;

    /** @var \Branch8\HotaiShipping\Helper\Data */
    protected $hotaiShippingHelper;

    /** @var GetCustomerNickname */
    private $customerNickname;

    /** @var OrderManagement */
    protected $orderManagement;

    /** @var Url */
    private $url;

    /** @var array|null */
    private $ga4RefundData;

    /** @var ProductHelper */
    private $productHelper;

    /** @var ResourceConnection */
    private $resourceConnection;

    /** @var array|null */
    private $rmaReasonIdToLabel;

    /** @var DataHelper */
    protected $dataHelper;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /**
     * Constructor
     *
     * @param Data $mpRmaHelper
     * @param DetailsFactory $details
     * @param ManagerInterface $messageManager
     * @param StatusLabel $statusLabel
     * @param Customer $customer
     * @param RmaStatus $status
     * @param ItemStatus $itemStatus
     * @param Item $orderItem
     * @param TimezoneInterface $localeDate
     * @param \Branch8\HotaiShipping\Helper\Data $hotaiShippingHelper
     * @param GetCustomerNickname $customerNickname
     * @param OrderManagement $orderManagement
     * @param Url $url
     * @param ProductHelper $productHelper
     * @param ResourceConnection $resourceConnection
     * @param DataHelper $dataHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Data $mpRmaHelper,
        DetailsFactory $details,
        ManagerInterface $messageManager,
        StatusLabel $statusLabel,
        Customer $customer,
        RmaStatus $status,
        ItemStatus $itemStatus,
        Item $orderItem,
        TimezoneInterface $localeDate,
        \Branch8\HotaiShipping\Helper\Data $hotaiShippingHelper,
        GetCustomerNickname $customerNickname,
        OrderManagement $orderManagement,
        Url $url,
        ProductHelper $productHelper,
        ResourceConnection $resourceConnection,
        DataHelper $dataHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->mpRmaHelper = $mpRmaHelper;
        $this->details = $details;
        $this->messageManager = $messageManager;
        $this->statusLabel = $statusLabel;
        $this->customer = $customer;
        $this->itemStatus = $itemStatus;
        $this->status = $status;
        $this->orderItem = $orderItem;
        $this->localeDate = $localeDate;
        $this->hotaiShippingHelper = $hotaiShippingHelper;
        $this->customerNickname = $customerNickname;
        $this->orderManagement = $orderManagement;
        $this->url = $url;
        $this->productHelper = $productHelper;
        $this->resourceConnection = $resourceConnection;
        $this->dataHelper = $dataHelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Create RMA by cancellation
     *
     * @param Order $order
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function createByCancellation(Order $order, array $data): string
    {
        $itemsId = $this->extractOrderItemIds($order);
        return $this->createCancellationRma($order, $itemsId, $data);
    }

    /**
     * Create RMA by PPS cancellation
     *
     * @param Order $order
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function createByPPSCancellation(Order $order, array $data): string
    {
        $itemsId = $data['items'] ?? [];
        return $this->createCancellationRma($order, $itemsId, $data);
    }

    /**
     * Create cancellation RMA (shared logic)
     *
     * @param Order $order
     * @param array $itemsId
     * @param array $data
     * @return string
     * @throws Exception
     */
    private function createCancellationRma(Order $order, array $itemsId, array $data): string
    {
        $status = $this->determineCancellationStatus($order);

        $rmaData = [
            'order_id' => $order->getId(),
            'order_items' => $itemsId,
            'resolution_type' => $this->mpRmaHelper::RESOLUTION_CANCEL,
            'status' => $status,
            'memo_id' => $data['memo_id'] ?? null,
        ];

        $this->initCreateRmaDetails($rmaData);
        $this->mpRmaHelper->updateMpOrder($order->getId(), $data['memo_id'] ?? null);
        
        return $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($rmaData['status']);
    }

    /**
     * Determine cancellation status based on invoice identifier
     *
     * @param Order $order
     * @return int
     */
    private function determineCancellationStatus(Order $order): int
    {
        return $order->getEcpayInvoiceCustomerIdentifier()
            ? \Branch8\Rma\Model\Rma\Status::RETURN_FINANCIAL_REVIEW_PROCESSING
            : \Branch8\Rma\Model\Rma\Status::RETURN_REFUND_PROCESSING;
    }

    /**
     * Extract item IDs from order
     *
     * @param Order $order
     * @return array
     */
    private function extractOrderItemIds(Order $order): array
    {
        $itemsId = [];
        foreach ($order->getAllItems() as $item) {
            $itemsId[] = $item->getItemId();
        }
        return $itemsId;
    }

    /**
     * Create RMA by seller
     *
     * @param array $rmaData
     * @return void
     * @throws Exception
     */
    public function createBySeller(array $rmaData): void
    {
        $this->setSender(SenderType::TYPE_SELLER);
        $this->initCreateRmaDetails($rmaData);
    }

    /**
     * Create RMA by customer
     *
     * @param array $rmaData
     * @return void
     * @throws Exception
     */
    public function createByCustomer(array $rmaData): void
    {
        $this->setSender(SenderType::TYPE_CUSTOMER);
        $this->initCreateRmaDetails($rmaData);
    }

    /**
     * Get RMA reason ID to label mapping
     *
     * @return array
     */
    public function rmaReasonIdToLabel(): array
    {
        if ($this->rmaReasonIdToLabel !== null) {
            return $this->rmaReasonIdToLabel;
        }

        $select = $this->resourceConnection->getConnection()->select();
        $select->from('marketplace_rma_reasons', ['id', 'reason']);
        $data = $this->resourceConnection->getConnection()->fetchPairs($select);
        
        $this->rmaReasonIdToLabel = $data ?: [];
        
        return $this->rmaReasonIdToLabel;
    }


    /**
     * Create RMA by admin
     *
     * @param array $rmaData
     * @return void
     * @throws Exception
     */
    public function createByAdmin(array $rmaData): void
    {
        $this->setSender(SenderType::TYPE_ADMIN);
        $this->initCreateRmaDetails($rmaData);
    }


    /**
     * Create RMA by Presco (updated by 統一數網)
     *
     * @param Order $order
     * @return self
     * @throws Exception
     */
    public function createByPresco(Order $order): self
    {
        $itemsId = $this->processPrescoOrderItems($order);
        $configData = $this->getPrescoConfigData();

        $rmaData = [
            'order_id' => $order->getId(),
            'order_items' => $itemsId,
            'resolution_type' => $this->mpRmaHelper::RESOLUTION_REFUND,
            'status' => \Branch8\Rma\Model\Rma\Status::RETURN_APPLY_PROCESSING,
            'is_system_refund' => true,
            'rma_receiver' => $configData['receiver'],
            'rma_address' => $configData['address'],
            'rma_reason' => $configData['reason'],
            'rma_phone' => $configData['phone'],
        ];

        $this->setSender(SenderType::TYPE_SYSTEM);
        $this->initCreateRmaDetails($rmaData);
        
        return $this;
    }

    /**
     * Process Presco order items and set RMA status
     *
     * @param Order $order
     * @return array
     */
    private function processPrescoOrderItems(Order $order): array
    {
        $itemsId = [];
        foreach ($order->getAllItems() as $item) {
            $item->setRmaStatus(\Branch8\Rma\Model\Rma\Status::RMA_PROCESSING);
            $item->save();
            $itemsId[] = $item->getItemId();
        }
        return $itemsId;
    }

    /**
     * Get Presco configuration data
     *
     * @return array
     */
    private function getPrescoConfigData(): array
    {
        $receiver = $this->scopeConfig->getValue(
            'hopes/convenience_store/rma_receiver',
            ScopeInterface::SCOPE_STORE
        );
        
        $reason = $this->scopeConfig->getValue(
            'hopes/convenience_store/rma_reason',
            ScopeInterface::SCOPE_STORE
        );
        
        $phoneRaw = $this->scopeConfig->getValue(
            'hopes/convenience_store/rma_phone',
            ScopeInterface::SCOPE_STORE
        );
        $phone = preg_replace('/\D+/', '', (string)$phoneRaw);

        $addressConfig = $this->scopeConfig->getValue(
            'hopes/convenience_store/rma_address',
            ScopeInterface::SCOPE_STORE
        );
        $addressList = array_values(
            array_filter(
                array_map('trim', preg_split('/\r?\n/', $addressConfig))
            )
        );

        return [
            'receiver' => $receiver,
            'reason' => $reason,
            'phone' => $phone,
            'address' => $addressList,
        ];
    }


    /**
     * Combine RMA Address follow format {Zipcode,City,Region,Address}
     *
     * @param array $rmaData
     * @return string
     */
    private function combineRmaAddress(array $rmaData): string
    {
        if (empty($rmaData['rma_address'])) {
            return '';
        }

        $this->ensureZipcodeInAddress($rmaData);

        $address = implode(',', array_filter($rmaData['rma_address'], function ($item) {
            return !empty($item);
        }));

        return $this->buildFullAddress($rmaData, $address);
    }

    /**
     * Ensure zipcode exists in address data
     *
     * @param array $rmaData
     * @return void
     */
    private function ensureZipcodeInAddress(array &$rmaData): void
    {
        if (empty($rmaData['rma_zipcode']) && !empty($rmaData['rma_city']) && !empty($rmaData['rma_region'])) {
            $rmaData['rma_zipcode'] = $this->dataHelper->queryPostcodeForAddress(
                $rmaData['rma_city'],
                $rmaData['rma_region']
            );
        } elseif (isset($rmaData['rma_address'][0]) && !is_numeric($rmaData['rma_address'][0])) {
            $region = $rmaData['rma_address'][0] ?? "";
            $city = $rmaData['rma_address'][1] ?? "";
            $zipcode = $this->dataHelper->queryPostcodeForAddress($city, $region);
            array_unshift($rmaData['rma_address'], $zipcode);
        }
    }

    /**
     * Build full address string
     *
     * @param array $rmaData
     * @param string $address
     * @return string
     */
    private function buildFullAddress(array $rmaData, string $address): string
    {
        $combine = [];
        
        if (isset($rmaData['rma_zipcode']) && $this->mpRmaHelper->cleanSpecialChar($rmaData['rma_zipcode'])) {
            $combine[] = $this->mpRmaHelper->cleanSpecialChar($rmaData['rma_zipcode']);
        }
        if (isset($rmaData['rma_region'])) {
            $combine[] = $rmaData['rma_region'];
        }
        if (isset($rmaData['rma_city'])) {
            $combine[] = $rmaData['rma_city'];
        }
        $combine[] = $address;
        
        return implode(',', $combine);
    }
    /**
     * Initialize and create RMA details
     * 
     * Uses database transaction with row-level locking to prevent race condition
     * when multiple requests try to create RMA for the same item simultaneously
     *
     * @param array $rmaData
     * @return void
     * @throws Exception
     */
    public function initCreateRmaDetails(array $rmaData): void
    {
        $connection = $this->resourceConnection->getConnection();
        
        // Start transaction to ensure atomicity and enable row-level locking
        $connection->beginTransaction();
        
        try {
            $this->validateRmaData($rmaData);
            
            $orderId = $rmaData['order_id'];
            $items = $rmaData['order_items'];
            $order = $this->mpRmaHelper->getOrder($orderId);
            $parentOrder = $this->orderManagement->getParentOrder(true, (int)$orderId);

            // Process order items and collect data (with locking)
            $itemProcessingResult = $this->processOrderItemsWithLocking($items, $orderId, $connection);
            
            // Prepare RMA data
            $rmaData = $this->prepareRmaData($rmaData, $order, $itemProcessingResult);
            
            // Save RMA model
            $rmaId = $this->saveRmaModel($rmaData);
            
            // Save RMA items
            $this->saveRmaItems($items, $rmaId, $rmaData['reason_id'] ?? 0, $rmaData);
            
            // Create RMA history and status records
            $this->createRmaHistoryAndStatus($rmaId, $rmaData, $items, $orderId);
            
            // Set instance data
            $this->setRmaDetailRecordId($rmaId);
            $this->setRmaDetailData($rmaData);
            
            // Commit transaction on success
            $connection->commit();
            
        } catch (Exception $e) {
            // Rollback transaction on any error
            $connection->rollBack();
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__, ['order_id' => $rmaData['order_id'] ?? null]);
            throw $e;
        }
    }

    /**
     * Validate RMA data
     *
     * @param array $rmaData
     * @return void
     * @throws Exception
     */
    private function validateRmaData(array $rmaData): void
    {
        $rmaPhone = $rmaData['rma_phone'] ?? '';
        if (strlen($rmaPhone) > self::MAX_PHONE_LENGTH) {
            throw new Exception(__('Phone number must be less or equal to ten.'));
        }
    }

    /**
     * Process order items with database row-level locking to prevent race condition
     * 
     * Uses SELECT FOR UPDATE to lock rows and ensure only one request can create RMA
     * for a given item at a time
     *
     * @param array $items
     * @param int $orderId
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @return array
     * @throws Exception
     */
    private function processOrderItemsWithLocking(array $items, int $orderId, $connection): array
    {
        $ga4RefundItems = [];
        $productIds = [];
        $ga4Value = 0;
        $productId = 0;

        foreach ($items as $index => $itemId) {
            // Use SELECT FOR UPDATE to lock the rows and prevent concurrent RMA creation
            $select = $connection->select()
                ->from(['items' => $connection->getTableName('marketplace_rma_items')], ['rma_id'])
                ->where('item_id = ?', $itemId)
                ->forUpdate(true);
            
            $existingRmaIds = $connection->fetchCol($select);
            
            if (!empty($existingRmaIds)) {
                // Check if any of the RMAs are active (not canceled)
                $selectDetails = $connection->select()
                    ->from(['details' => $connection->getTableName('marketplace_rma_details')], ['id'])
                    ->where('id IN (?)', $existingRmaIds)
                    ->where('status NOT IN (?)', [
                        \Branch8\Rma\Model\Rma\Status::RMA_CANCELED,
                        \Branch8\Rma\Model\Rma\Status::RETURN_APPLY_CANCEL,
                        \Branch8\Rma\Model\Rma\Status::REPLACE_APPLY_CANCEL
                    ]);
                
                $activeRmaIds = $connection->fetchCol($selectDetails);
                
                if (!empty($activeRmaIds)) {
                    throw new Exception(__('The RMA has already been created and cannot be created again.'));
                }
            }

            /** @var Item $orderItem */
            $orderItem = $this->orderItem->load($itemId);
            $productId = $orderItem->getProductId();
            $productIds[$itemId] = $productId;
            $ga4Value += $orderItem->getRowTotalInclTax();
            $ga4RefundItems[] = $this->getGa4ItemData($orderItem, $index);
        }

        return [
            'product_ids' => $productIds,
            'product_id' => $productId,
            'ga4_refund_items' => $ga4RefundItems,
            'ga4_value' => $ga4Value,
        ];
    }

    /**
     * Process order items and collect GA4 data (legacy method without locking)
     * 
     * @deprecated Use processOrderItemsWithLocking instead
     * @param array $items
     * @param int $orderId
     * @return array
     * @throws Exception
     */
    private function processOrderItems(array $items, int $orderId): array
    {
        $ga4RefundItems = [];
        $productIds = [];
        $ga4Value = 0;
        $productId = 0;

        foreach ($items as $index => $itemId) {
            $isActive = $this->mpRmaHelper->checkIsActiveRma($itemId);
            if ($isActive) {
                throw new Exception(__('The RMA has already been created and cannot be created again.'));
            }

            /** @var Item $orderItem */
            $orderItem = $this->orderItem->load($itemId);
            $productId = $orderItem->getProductId();
            $productIds[$itemId] = $productId;
            $ga4Value += $orderItem->getRowTotalInclTax();
            $ga4RefundItems[] = $this->getGa4ItemData($orderItem, $index);
        }

        return [
            'product_ids' => $productIds,
            'product_id' => $productId,
            'ga4_refund_items' => $ga4RefundItems,
            'ga4_value' => $ga4Value,
        ];
    }

    /**
     * Prepare RMA data array
     *
     * @param array $rmaData
     * @param Order $order
     * @param array $itemProcessingResult
     * @return array
     */
    private function prepareRmaData(array $rmaData, Order $order, array $itemProcessingResult): array
    {
        $orderId = $rmaData['order_id'];
        $reasonId = $rmaData['reason_id'] ?? 0;
        $reasonIdToLabel = $this->rmaReasonIdToLabel();
        $time = date(self::DATABASE_DATE_FORMAT);
        $parentOrder = $this->orderManagement->getParentOrder(true, (int)$orderId);
        
        $customer = $this->customer->load($order->getCustomerId());
        $sellerId = $this->mpRmaHelper->getSellerIdByProductId(
            $itemProcessingResult['product_id'],
            $orderId
        );

        // Prepare GA4 refund data
        $this->ga4RefundData = [
            'event' => "refund",
            'ecommerce' => [
                'transaction_id' => $order->getIncrementId(),
                'currency' => $order->getOrderCurrencyCode(),
                'value' => $this->productHelper->formatMoney($itemProcessingResult['ga4_value']),
                'items' => $itemProcessingResult['ga4_refund_items']
            ]
        ];

        // Build RMA data
        $rmaData['product_id'] = implode(",", $itemProcessingResult['product_ids']);
        $rmaData['seller_id'] = $sellerId;
        $rmaData['product_seller'] = $this->getSellerName($sellerId);
        $rmaData['rma_reason_id'] = $reasonId;
        $rmaData['rma_reason'] = $reasonIdToLabel[$reasonId] ?? self::DEFAULT_RMA_REASON;
        $rmaData['rma_reason_additional_info'] = $rmaData['additional_info'] ?? '';
        $rmaData['customer_tax_id_number'] = $order->getEcpayInvoiceCustomerIdentifier();
        $rmaData['customer_id'] = $order->getCustomerId();
        $rmaData['customer_name'] = $order->getCustomerFirstname() ?? $customer->getName();
        $rmaData['order_id'] = $orderId;
        $rmaData['customer_email'] = $order->getCustomerEmail() ?? $customer->getBuyerEmail();
        $rmaData['seller_status'] = Data::SELLER_STATUS_PENDING;
        $rmaData['status'] = $rmaData['status'] ?? $this->mpRmaHelper->iniRmaStatus($rmaData['resolution_type']);
        $rmaData['created_date'] = $time;
        $rmaData['updated_date'] = $time;
        $rmaData['order_ref'] = '#' . $order->getIncrementId();
        $rmaData['rma_receiver'] = $rmaData['rma_receiver'] ?? '';
        $rmaData['rma_address'] = $this->combineRmaAddress($rmaData);
        $rmaData['rma_phone'] = $rmaData['rma_phone'] ?? '';
        $rmaData['rma_delivery_time'] = $this->formatDeliveryTime($rmaData['rma_delivery_time'] ?? null);
        $rmaData['rma_application_details'] = $this->mpRmaHelper->jsonEncodeData($rmaData);
        $rmaData['order_data'] = $this->getRmaOrderData($rmaData);

        return $rmaData;
    }

    /**
     * Get seller name by seller ID
     *
     * @param int|null $sellerId
     * @return string
     */
    private function getSellerName(?int $sellerId): string
    {
        if ($sellerId) {
            $seller = $this->customer->load($sellerId);
            return $seller->getName() ?? 'Seller';
        }
        return 'Admin';
    }

    /**
     * Format delivery time
     *
     * @param mixed $deliveryTime
     * @return string
     */
    private function formatDeliveryTime($deliveryTime): string
    {
        if ($deliveryTime == null) {
            return '';
        }
        if (is_array($deliveryTime)) {
            return implode(',', $deliveryTime);
        }
        return (string)$deliveryTime;
    }

    /**
     * Save RMA model
     *
     * @param array $rmaData
     * @return int
     */
    private function saveRmaModel(array $rmaData): int
    {
        $model = $this->details->create();
        $model->setData($rmaData)->save();
        return (int)$model->getId();
    }

    /**
     * Save RMA items
     *
     * @param array $items
     * @param int $rmaId
     * @param int $reasonId
     * @param array $rmaData
     * @return void
     * @throws Exception
     */
    private function saveRmaItems(array $items, int $rmaId, int $reasonId, array $rmaData): void
    {
        $model = $this->details->create()->load($rmaId);
        
        foreach ($items as $itemId) {
            $orderItem = $this->orderItem->load($itemId);
            $itemData = [
                'rma_id' => $rmaId,
                'item_id' => $itemId,
                'reason_id' => $reasonId,
                'product_id' => $orderItem->getProductId(),
                'qty' => $orderItem->getQtyOrdered(),
                'price' => $orderItem->getRowTotalInclTax(),
                'point' => $orderItem->getRowTotalPointUsed(),
            ];

            try {
                $this->mpRmaHelper->saveItemData($itemData);
            } catch (Exception $e) {
                \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Branch8\Rma\Helper\Log::class)
                    ->exception($e, self::LOG_OPTION, __METHOD__, ['rma_id' => $rmaId, 'item_id' => $itemId]);
                $model->delete();
                $this->messageManager->addError(__('There is some problem in generating RMA.'));
                throw new Exception(__('There is some problem in generating RMA.'));
            }
        }
    }

    /**
     * Create RMA history and status records
     *
     * @param int $rmaId
     * @param array $rmaData
     * @param array $items
     * @param int $orderId
     * @return void
     */
    private function createRmaHistoryAndStatus(int $rmaId, array $rmaData, array $items, int $orderId): void
    {
        $sender = $this->sender ?? SenderType::TYPE_CUSTOMER;
        $this->mpRmaHelper->saveRmaHistory(
            $rmaId,
            $this->mpRmaHelper->getConfigData('new_rma_message'),
            $sender
        );

        $model = $this->details->create()->load($rmaId);
        $status = $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($rmaData['status']);
        $this->status->createStatusRecord($status, $model);
        
        foreach ($items as $itemId) {
            $this->itemStatus->updateItemStatusById($itemId, $status, $orderId, true, $rmaId);
        }
    }

    /**
     * Get GA4 item data for refund tracking
     *
     * @param Item $orderItem
     * @param int $index
     * @return array
     */
    protected function getGa4ItemData(Item $orderItem, int $index): array
    {
        $ga4RefundItem = $this->productHelper->getDetailProductPush($orderItem->getProduct(), $index);
        $ga4RefundItem['affiliation'] = $this->productHelper->getSellerByProductId(
            $orderItem->getProduct()->getRowId()
        );
        $ga4RefundItem['quantity'] = $this->productHelper->formatQty($orderItem->getQtyOrdered());
        $ga4RefundItem['price'] = $this->productHelper->formatMoney($orderItem->getRowTotalInclTax());
        $ga4RefundItem['discount'] = $this->productHelper->formatMoney($orderItem->getDiscountAmount());
        
        $buyRequest = $orderItem->getBuyRequest()->getData();
        $ga4RefundItem['item_variant'] = $this->productHelper->checkVariantForProduct(
            $orderItem->getProduct(),
            $buyRequest
        );

        // Remove unnecessary fields
        unset(
            $ga4RefundItem['item_list_id'],
            $ga4RefundItem['item_list_name'],
            $ga4RefundItem['promotion_id'],
            $ga4RefundItem['promotion_name']
        );

        return $ga4RefundItem;
    }

    /**
     * Set RMA detail record ID
     *
     * @param int $rmaId
     * @return void
     */
    public function setRmaDetailRecordId(int $rmaId): void
    {
        $this->rmaDetailRecordId = $rmaId;
    }

    /**
     * Get GA4 refund data
     *
     * @return array|null
     */
    public function getGA4RefundData(): ?array
    {
        return $this->ga4RefundData;
    }

    /**
     * Get RMA detail record ID
     *
     * @return int|null
     */
    public function getRmaDetailRecordId(): ?int
    {
        return $this->rmaDetailRecordId;
    }

    /**
     * Set RMA detail data
     *
     * @param array $rmaData
     * @return void
     */
    public function setRmaDetailData(array $rmaData): void
    {
        $this->rmaDetailData = $rmaData;
    }

    /**
     * Get RMA detail data
     *
     * @return array|null
     */
    public function getRmaDetailData(): ?array
    {
        return $this->rmaDetailData;
    }

    /**
     * Get flow status
     *
     * @return string|null
     */
    public function getFlowStatus(): ?string
    {
        return $this->flowStatus;
    }

    /**
     * Set sender type
     *
     * @param string $sender
     * @return void
     */
    public function setSender(string $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * Get formatted order date
     *
     * @param string $createdAt
     * @return string
     */
    public function getOrderDate(string $createdAt): string
    {
        try {
            return $this->localeDate->date($createdAt)->format(self::ORDER_DATE_FORMAT);
        } catch (\Exception $exception) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($exception, self::LOG_OPTION, __METHOD__);
            return '';
        }
    }

    /**
     * Get order URL detail
     *
     * @param string|null $parentOrder
     * @return string
     */
    public function getOrderUrlDetail(?string $parentOrder): string
    {
        if ($parentOrder) {
            return $this->url->getUrl('sales/parentOrder/history', ['_query' => ['search' => $parentOrder]]);
        }
        return $this->url->getUrl('sales/parentOrder/history');
    }

    /**
     * Get RMA order data
     *
     * @param array $rmaData
     * @return array
     */
    public function getRmaOrderData(array $rmaData): array
    {
        $orderId = $rmaData['order_id'];
        $order = $this->mpRmaHelper->getOrder($orderId);
        $parentOrder = $this->orderManagement->getParentOrder(true, (int)$orderId);

        return [
            'increment_id' => $order->getData('hotai_child_order_number'),
            'customer_name' => $this->customerNickname->getCustomerNicknameByCustomerId($order->getCustomerId()),
            'frontend_status_label' => $order->getFrontendStatusLabel(),
            'is_not_virtual' => $order->getIsNotVirtual(),
            'shipping_description' => $this->hotaiShippingHelper->getCarrierTitleByCarrierMethoCode(
                $order->getShippingMethod()
            ),
            'email_customer_note' => $order->getCustomerNote(),
            'grandTotal' => $order->getOrderCurrency()->formatPrecision($order->getGrandTotal(), 2),
            'subtotal' => $order->getOrderCurrency()->formatPrecision($order->getSubtotal(), 2),
            'subtotal_incl_tax' => $order->getOrderCurrency()->formatPrecision($order->getSubtotalInclTax(), 2),
            'shipping_amount' => $order->getOrderCurrency()->formatPrecision($order->getShippingAmount(), 2),
            'shipping_incl_tax' => $order->getOrderCurrency()->formatPrecision($order->getShippingInclTax(), 2),
            'created_at' => $this->getOrderDate($order->getCreatedAt()),
            'url_history' => $this->getOrderUrlDetail($parentOrder->getIncrementId()),
            'store' => $order->getStore(),
        ];
    }

}