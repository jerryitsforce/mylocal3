<?php

namespace Ecpay\General\Helper\Services\Common;

use Ecpay\General\Helper\Foundation\GeneralHelper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Convert\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Shipping\Model\ShipmentNotifier;
use Ecpay\General\Model\EcpayLogisticFactory;
use Ecpay\General\Model\EcpayPaymentInfoFactory;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Magento\Framework\Exception\NoSuchEntityException;
use Zend_Log_Exception;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Branch8\HotaiCore\Model\Order\Status as OrderStatus;

class OrderService extends AbstractHelper
{
    protected GeneralHelper $_loggerInterface;
    protected OrderInterface $_orderInterface;
    protected EcpayLogisticFactory $_ecpayLogisticFactory;
    protected EcpayPaymentInfoFactory $_ecpayPaymentInfoFactory;
    protected InvoiceService $_invoiceService;
    protected Transaction $_transaction;
    protected CollectionFactory $_orderCollectionFactory;
    protected Order $_convertOrder;
    protected OrderFactory $_orderFactory;
    protected ShipmentNotifier $_shipmentNotifier;
    protected PaymentService $_paymentService;
    protected OrderRepositoryInterface $_orderRepository;
    protected $orderRepository;
    protected ResourceConnection $resourceConnection;
    protected InvoiceRepositoryInterface $_invoiceRepository;

    /**
     * @var Resource
     */
    protected $_resource;

    public function __construct(
        Context $context,
        InvoiceService $invoiceService,
        Transaction $transaction,
        GeneralHelper $loggerInterface,
        OrderInterface $orderInterface,
        EcpayLogisticFactory $ecpayLogisticFactory,
        EcpayPaymentInfoFactory $ecpayPaymentInfoFactory,
        CollectionFactory $orderCollectionFactory,
        Order $convertOrder,
        OrderFactory $orderFactory,
        ShipmentNotifier $shipmentNotifier,
        PaymentService $paymentService,
        ResourceConnection $resource,
        OrderRepositoryInterface $orderRepository,
        ResourceConnection $resourceConnection,
        InvoiceRepositoryInterface $invoiceRepository
    ) {
        parent::__construct($context);
        $this->_loggerInterface         = $loggerInterface;
        $this->_orderInterface          = $orderInterface;
        $this->_ecpayLogisticFactory    = $ecpayLogisticFactory;
        $this->_ecpayPaymentInfoFactory = $ecpayPaymentInfoFactory;
        $this->_invoiceService          = $invoiceService;
        $this->_transaction             = $transaction;
        $this->_orderCollectionFactory  = $orderCollectionFactory;
        $this->_convertOrder            = $convertOrder;
        $this->_orderFactory            = $orderFactory;
        $this->_shipmentNotifier        = $shipmentNotifier;
        $this->_paymentService          = $paymentService;
        $this->_orderRepository         = $orderRepository;
        $this->_resource                = $resource;
        $this->resourceConnection       = $resourceConnection;
        $this->_invoiceRepository       = $invoiceRepository;
    }

    /**
     * 訂單編號組合
     * @param string $orderId
     * @param string $prefix
     * @return string
     */
    public function getMerchantTradeNo($orderId, $prefix = '')
    {
        $merchantTradeNo = $prefix . substr(str_pad($orderId, 8, '0', STR_PAD_LEFT), 0, 8) . 'SN' . substr(
                hash(
                    'sha256',
                    (string)time()
                ),
                -5
            );
        return substr($merchantTradeNo, 0, 20);
    }

    /**
     * 利用Payment MerchantTradeNo取得訂單資訊
     * @param string $merchantTradeNo
     * @return array
     */
    public function getOrderIdByPaymentMerchantTradeNo($merchantTradeNo)
    {
        $info = [];

        $orderModel = $this->_orderFactory->create();

        $collection = $orderModel->getCollection()->addFieldToFilter(
            'ecpay_payment_merchant_trade_no',
            ['eq' => $merchantTradeNo]
        )->setOrder('entity_id', 'DESC')->setCurPage(1)->setPageSize(1);

        foreach ($collection as $item) {
            $info = $item->getData();
        }

        return $info;
    }

    /**
     * 取得可執行自動開立發票程序的訂單編號
     * @return array $ids
     */
    public function getOrderForInvoiceAutoProcedure(): array
    {
        $ids        = [];
        $connection = $this->resourceConnection->getConnection();
        $targetStatus = implode(',', [
            "'".OrderStatus::STATUS_PROCESSING."'",
            "'".OrderStatus::STATUS_PENDING_COMPLETE."'",
            "'".OrderStatus::STATUS_COMPLETE."'"
        ]);

        $select = $connection->select()->from(['so' => 'sales_order'], ['entity_id'])
            ->join(
            ['spoc' => 'sales_parent_order_children'],
            'so.entity_id = spoc.children_id',
            [] // 不需要從 sales_parent_order_children 中選取其他欄位
        )->where(
            "state in ({$targetStatus}) AND ecpay_invoice_tag = 0 AND is_paid = 1"
        )->order('so.updated_at DESC')->limit(10);

        $collection =  $connection->fetchAll($select);

        foreach ($collection as $item) {
            $ids[] = $item['entity_id'];
        }

        return $ids;
    }

    /**
     * 取得可執行自動開立物流訂單程序的訂單編號
     * @return array $ids
     */
    public function getOrderForLogisticAutoProcedure()
    {
        $ids        = [];
        $collection = $this->_orderCollectionFactory->create()->addAttributeToSelect('entity_id')->addFieldToFilter(
            'ecpay_logistic_auto_tag',
            ['eq' => 1]
        )->addFieldToFilter('ecpay_shipping_tag', ['eq' => 0]);

        foreach ($collection as $item) {
            $ids[] = $item->getData('entity_id');
        }

        return $ids;
    }

    /**
     * 取得訂單
     * @param  $orderId
     * @return OrderInterface
     */
    public function getOrder($orderId): OrderInterface
    {
        try {
            return $this->orderRepository = $this->_orderRepository->get($orderId);
        } catch (NoSuchEntityException $exception) {
            throw new LocalizedException(__('This order no longer exists.'));
        }

    }

    /**
     * 取得IncrementId
     * @param string $orderId
     * @return string
     */
    public function getIncrementId($orderId)
    {
        return $this->getOrder($orderId)->getIncrementId();
    }

    /**
     * 取得實際的訂單編號
     * @param string $orderId
     * @return string
     */
    public function getRealOrderId($orderId)
    {
        return $this->getOrder($orderId)->getRealOrderId();
    }

    /**
     * 取得訂單建立時間
     * @param string $orderId
     * @return string
     */
    public function getCreatedAt($orderId)
    {
        return $this->getOrder($orderId)->getCreatedAt();
    }

    /**
     * 取得訂單最新更新時間
     * @param string $orderId
     * @throws LocalizedException
     */
    public function getUpdatedAt($orderId)
    {
        return $this->getOrder($orderId)->getUpdatedAt();
    }

    /**
     * 取得訂單狀態
     * @param string $orderId
     * @return string
     */
    public function getStatus($orderId)
    {
        return $this->getOrder($orderId)->getStatus();
    }

    /**
     * 取得訂單的 protect code 欄位
     * @param string $orderId
     * @return string
     */
    public function getProtectCode($orderId)
    {
        return $this->getOrder($orderId)->getProtectCode();
    }

    /**
     * 取得付款方式
     * @param string $orderId
     * @return string
     */
    public function getPaymentMethod($orderId)
    {
        $payment = $this->getOrder($orderId)->getPayment();
        $method  = $payment->getMethod();

        return $method;
    }

    /**
     * 取得額外資訊
     * @param string $orderId
     * @return string
     */
    public function getAdditionalInformation($orderId)
    {
        $payment = $this->getOrder($orderId)->getPayment();
        $info    = $payment->getAdditionalInformation();

        return $info;
    }

    /**
     * 取得幣別
     * @param string $orderId
     * @return string
     */
    public function getOrderCurrencyCode($orderId)
    {
        return $this->getOrder($orderId)->getOrderCurrencyCode();
    }

    /**
     * 取得訂單折扣金額
     * @param string $orderId
     * @return string
     */
    public function getBaseDiscountAmount($orderId)
    {
        return $this->getOrder($orderId)->getBaseDiscountAmount();
    }

    /**
     * 取得訂單物流金額
     * @param $orderId
     * @return float|null
     */
    public function getBaseShippingAmount($orderId)
    {
        return $this->getOrder($orderId)->getBaseShippingAmount();
    }

    /**
     * 取得訂單物流金額(含稅)
     * @param $orderId
     * @return float|null
     */
    public function getBaseShippingInclTax($orderId)
    {
        return $this->getOrder($orderId)->getBaseShippingInclTax();
    }

    /**
     * 取得訂單小計金額(未稅)
     * @param string $orderId
     * @return string
     */
    public function getBaseSubtotal($orderId)
    {
        return $this->getOrder($orderId)->getBaseSubtotal();
    }

    /**
     * 取得訂單總金額 - store base currency grand total
     * @param string $orderId
     * @return string
     */
    public function getBaseGrandTotal($orderId)
    {
        return $this->getOrder($orderId)->getBaseGrandTotal();
    }

    /**
     * 取得訂單總金額(GrandTotal)
     * @param string $orderId
     * @return string
     */
    public function getGrandTotal($orderId)
    {
        return $this->getOrder($orderId)->getGrandTotal();
    }

    /**
     * 取得訂單總金額(BaseTotalPaid)
     * @param string $orderId
     * @return string
     */
    public function getBaseTotalPaid($orderId)
    {
        return $this->getOrder($orderId)->getBaseTotalPaid();
    }

    /**
     * 取得訂單總金額(TotalPaid)
     * @param string $orderId
     * @return string
     */
    public function getTotalPaid($orderId)
    {
        return $this->getOrder($orderId)->getTotalPaid();
    }

    /**
     * 取得訂單重量
     * @param string $orderId
     * @return string
     */
    public function getWeight($orderId)
    {
        return $this->getOrder($orderId)->getWeight();
    }

    /**
     * 取得訂購人姓名
     * @param string $orderId
     * @return string
     */
    public function getCustomerFirstname($orderId)
    {
        return $this->getOrder($orderId)->getCustomerFirstname();
    }

    /**
     * 取得訂購人姓名
     * @param string $orderId
     * @return string
     */
    public function getCustomerLastname($orderId)
    {
        return $this->getOrder($orderId)->getCustomerLastname();
    }

    /**
     * 取得訂購人Email
     * @param string $orderId
     * @return string
     */
    public function getCustomerEmail($orderId)
    {
        return $this->getOrder($orderId)->getCustomerEmail();
    }

    /**
     * 取得帳單地址
     * @param string $orderId
     * @return OrderAddressInterface
     */
    public function getBillingAddress($orderId)
    {
        return $this->getOrder($orderId)->getBillingAddress();
    }

    /**
     * 取得帳單城市
     * @param string $orderId
     * @return string
     */
    public function getBillingCity($orderId)
    {
        return $this->getOrder($orderId)->getBillingAddress()->getCity();
    }

    /**
     * 取得帳單區域
     * @param string $orderId
     * @return string
     */
    public function getBillingRegion($orderId)
    {
        return $this->getOrder($orderId)->getBillingAddress()->getRegion();
    }

    /**
     * 取得帳單郵遞區號
     * @param $orderId
     * @return string
     * @throws LocalizedException
     */
    public function getBillingPostcode($orderId): string
    {
        $postCode = $this->getOrder($orderId)->getBillingAddress()->getPostcode();
        return ($postCode === '-' || $postCode === '000') ? '' : $postCode;
    }

    /**
     * 取得訂單使用點數
     * @param string $orderId
     * @return int
     */
    public function getPointUsedTotal($orderId): int
    {
        return (int) $this->getOrder($orderId)->getPointUsedTotal();
    }

    /**
     * 取得子訂單編號
     * @param string $orderId
     * @return string
     */
    public function getHotaiChildOrderNumber($orderId): string
    {
        return $this->getOrder($orderId)->getHotaiChildOrderNumber();
    }

    /**
     * 取得帳單街道
     * @param string $orderId
     * @return string
     */
    public function getBillingStreet($orderId)
    {
        $street = $this->getOrder($orderId)->getBillingAddress()->getStreet();

        return $street[0];
    }

    /**
     * 取得帳單收件人
     * @param string $orderId
     * @return string
     */
    public function getBillingName($orderId)
    {
        $firstName = $this->getOrder($orderId)->getBillingAddress()->getFirstname();
        $lastName  = $this->getOrder($orderId)->getBillingAddress()->getlastname();

        return $lastName . $firstName;
    }

    /**
     * 取得帳單連絡電話
     * @param string $orderId
     * @return string
     */
    public function getBillingTelephone($orderId)
    {
        return $this->getOrder($orderId)->getBillingAddress()->getTelephone();
    }

    /**
     * 取得帳單電子郵件
     * @param string $orderId
     * @return class
     */
    public function getBillingEmail($orderId)
    {
        return $this->getOrder($orderId)->getBillingAddress()->getEmail();
    }

    /**
     * 取得帳單公司名稱
     * @param string $orderId
     * @return class
     */
    public function getBillingCompany($orderId)
    {
        return $this->getOrder($orderId)->getBillingAddress()->getCompany();
    }

    /**
     * 取得收件人
     * @param string $orderId
     * @return string
     */
    public function getShippingName($orderId)
    {
        $firstName = $this->getOrder($orderId)->getShippingAddress()->getFirstname();
        $lastName  = $this->getOrder($orderId)->getShippingAddress()->getLastname();

        return $lastName . $firstName;
    }

    /**
     * 取得收件人電話
     * @param string $orderId
     * @return string
     */
    public function getShippingTelephone($orderId)
    {
        return $this->getOrder($orderId)->getShippingAddress()->getTelephone();
    }

    /**
     * 取得收件人郵遞區號
     * @param string $orderId
     * @return string
     */
    public function getShippingPostcode($orderId)
    {
        return $this->getOrder($orderId)->getShippingAddress()->getPostcode();
    }

    /**
     * 取得收件人街道
     * @param string $orderId
     * @return string
     */
    public function getShippingStreet($orderId)
    {
        $street = $this->getOrder($orderId)->getShippingAddress()->getStreet();

        return $street[0];
    }

    /**
     * Get formatted order created date in store timezone
     * @param string $orderId
     * @param string $format
     * @return string
     */
    public function getCreatedAtFormatted($orderId, $format)
    {
        return $this->getOrder($orderId)->getCreatedAtFormatted($format);
    }

    /**
     * 寫入備註
     * @param string $orderId
     * @param boolean $comment
     * @param boolean $status
     * @param boolean $isVisibleOnFront
     */
    public function setOrderCommentForBack($orderId, $comment = '', $status = false, $isVisibleOnFront = false)
    {
        $order = $this->getOrder($orderId);
        $order->addCommentToStatusHistory($comment, $status, $isVisibleOnFront);
        $order->save();
    }

    /**
     * 更新訂單狀態
     * @param string $orderId
     * @param string $state
     */
    public function setOrderState($orderId, $state)
    {
        $order = $this->getOrder($orderId);
        $order->setState($state);
        $order->save();
    }

    /**
     * 取得訂單狀態
     * @param string $orderId
     */
    public function getOrderState($orderId)
    {
        $order = $this->getOrder($orderId);
        return $order->getState();
    }

    /**
     * 更新訂單狀態
     * @param string $orderId
     * @param string $status
     */
    public function setOrderStatus($orderId, $status)
    {
        $order = $this->getOrder($orderId);
        $order->setStatus($status);
        $order->save();
    }

    /**
     * 取得訂單狀態
     * @param string $orderId
     * @throws LocalizedException
     */
    public function getOrderStatus($orderId)
    {
        return $this->getOrder($orderId)->getStatus();
    }

    /**
     * @param $orderId
     * @return void
     * @throws LocalizedException
     */
    public function setIsInProcess($orderId)
    {
        $order = $this->getOrder($orderId);
        $order->setIsInProcess(true);
        $order->save();
    }

    /**
     * 更新訂單欄位 - 優化版本（含交易包裝）
     * @param int|string $orderId 訂單ID
     * @param string|array $key 欄位名稱或欄位陣列
     * @param mixed $value 欄位值（當$key為陣列時此參數忽略）
     * @param bool $useResource 是否使用直接資料庫更新（預設true）
     * @return bool 更新是否成功
     * @throws LocalizedException
     */
    public function setOrderData($orderId, $key = '', $value = '', bool $useResource = true): bool
    {
        // 輸入驗證
        if (empty($orderId) || empty($key)) {
            $this->_loggerInterface->writeLog('setOrderData: Invalid parameters - orderId: ' . $orderId . ', key: ' . $key);
            return false;
        }

        try {
            if ($useResource) {
                return $this->setOrderDataUsingResourceWithTransaction($orderId, $key, $value);
            }

            // 使用 Magento ORM 方式更新（已包含交易處理）
            $order = $this->getOrder($orderId);

            // 支援批量更新
            if (is_array($key)) {
                foreach ($key as $field => $fieldValue) {
                    $order->setData($field, $fieldValue);
                }
                $this->_loggerInterface->writeLog('setOrderData: Batch update for order ' . $orderId . ' - fields: ' . implode(', ', array_keys($key)));
            } else {
                $order->setData($key, $value);
                $this->_loggerInterface->writeLog('setOrderData: Single field update for order ' . $orderId . ' - ' . $key . ': ' . $value);
            }

            $this->_orderRepository->save($order);
            return true;

        } catch (\Exception $e) {
            $this->_loggerInterface->writeLog('setOrderData: Error updating order ' . $orderId . ' - ' . $e->getMessage());
            throw new LocalizedException(__('Failed to update order data: %1', $e->getMessage()));
        }
    }

    /**
     * 使用直接資料庫更新訂單欄位 - 優化版本（含交易包裝）
     * @param int|string $orderId 訂單ID
     * @param string|array $key 欄位名稱或欄位陣列
     * @param mixed $value 欄位值（當$key為陣列時此參數忽略）
     * @return bool 更新是否成功
     * @throws LocalizedException
     */
    public function setOrderDataUsingResourceWithTransaction($orderId, $key = '', $value = ''): bool
    {
        // 輸入驗證
        if (empty($orderId) || empty($key)) {
            $this->_loggerInterface->writeLog('setOrderDataUsingResourceWithTransaction: Invalid parameters - orderId: ' . $orderId . ', key: ' . $key);
            return false;
        }

        $connection = $this->_resource->getConnection();

        try {
            // 開始交易
            $connection->beginTransaction();
            $this->_loggerInterface->writeLog('setOrderDataUsingResourceWithTransaction: Starting transaction for order ' . $orderId);

            $tableName = $connection->getTableName('sales_order');

            // 準備更新資料
            $updateData = is_array($key) ? $key : [$key => $value];

            // 執行更新
            $affectedRows = $connection->update(
                $tableName,
                $updateData,
                ['entity_id = ?' => $orderId]
            );

            if ($affectedRows === 0) {
                $this->_loggerInterface->writeLog('setOrderDataUsingResourceWithTransaction: No rows updated for order ' . $orderId);
                $connection->rollBack();
                return false;
            }

            // 提交交易
            $connection->commit();

            $fields = is_array($key) ? implode(', ', array_keys($key)) : $key;
            $this->_loggerInterface->writeLog('setOrderDataUsingResourceWithTransaction: Successfully updated order ' . $orderId . ' - fields: ' . $fields . ' (affected rows: ' . $affectedRows . ')');

            return true;

        } catch (\Exception $e) {

            $connection->rollBack();
            $this->_loggerInterface->writeLog('setOrderDataUsingResourceWithTransaction: Error updating order ' . $orderId . ' - ' . $e->getMessage() . ' - Transaction rolled back');
            throw new LocalizedException(__('Failed to update order data using resource: %1', $e->getMessage()));
        }
    }

    /**
     * 使用直接資料庫更新訂單欄位 - 原始版本（無交易包裝）
     * @param int|string $orderId 訂單ID
     * @param string|array $key 欄位名稱或欄位陣列
     * @param mixed $value 欄位值（當$key為陣列時此參數忽略）
     * @return bool 更新是否成功
     * @throws LocalizedException
     */
    public function setOrderDataUsingResource($orderId, $key = '', $value = ''): bool
    {
        // 輸入驗證
        if (empty($orderId) || empty($key)) {
            $this->_loggerInterface->writeLog('setOrderDataUsingResource: Invalid parameters - orderId: ' . $orderId . ', key: ' . $key);
            return false;
        }

        try {
            $connection = $this->_resource->getConnection();
            $tableName = $connection->getTableName('sales_order');

            // 準備更新資料
            $updateData = is_array($key) ? $key : [$key => $value];

            // 執行更新
            $affectedRows = $connection->update(
                $tableName,
                $updateData,
                ['entity_id = ?' => $orderId]
            );

            if ($affectedRows === 0) {
                $this->_loggerInterface->writeLog('setOrderDataUsingResource: No rows updated for order ' . $orderId);
                return false;
            }

            $fields = is_array($key) ? implode(', ', array_keys($key)) : $key;
            $this->_loggerInterface->writeLog('setOrderDataUsingResource: Successfully updated order ' . $orderId . ' - fields: ' . $fields . ' (affected rows: ' . $affectedRows . ')');

            return true;

        } catch (\Exception $e) {
            $this->_loggerInterface->writeLog('setOrderDataUsingResource: Error updating order ' . $orderId . ' - ' . $e->getMessage());
            throw new LocalizedException(__('Failed to update order data using resource: %1', $e->getMessage()));
        }
    }

    /**
     * 批量更新訂單欄位 - 高效能版本（含交易包裝）
     * @param int|string $orderId 訂單ID
     * @param array $data 要更新的欄位陣列 ['field1' => 'value1', 'field2' => 'value2']
     * @param bool $useResource 是否使用直接資料庫更新（預設true，效能較佳）
     * @return bool 更新是否成功
     * @throws LocalizedException
     */
    public function setOrderDataBatch($orderId, array $data, bool $useResource = true): bool
    {
        if (empty($orderId) || empty($data) || !is_array($data)) {
            $this->_loggerInterface->writeLog('setOrderDataBatch: Invalid parameters - orderId: ' . $orderId . ', data: ' . print_r($data, true));
            return false;
        }

        try {
            if ($useResource) {
                return $this->setOrderDataUsingResourceWithTransaction($orderId, $data);
            }

            // 使用 Magento ORM 方式批量更新（已包含交易處理）
            $order = $this->getOrder($orderId);

            foreach ($data as $field => $value) {
                $order->setData($field, $value);
            }

            $this->_orderRepository->save($order);
            $this->_loggerInterface->writeLog('setOrderDataBatch: Successfully updated order ' . $orderId . ' - fields: ' . implode(', ', array_keys($data)));

            return true;

        } catch (\Exception $e) {
            $this->_loggerInterface->writeLog('setOrderDataBatch: Error updating order ' . $orderId . ' - ' . $e->getMessage());
            throw new LocalizedException(__('Failed to batch update order data: %1', $e->getMessage()));
        }
    }

    /**
     * 手動交易控制 - 用於複雜的多步驟操作
     * @param callable $callback 要在交易中執行的回調函數
     * @return mixed 回調函數的返回值
     * @throws LocalizedException
     */
    public function executeInTransaction(callable $callback)
    {
        $connection = $this->_resource->getConnection();

        try {
            $connection->beginTransaction();
            $this->_loggerInterface->writeLog('executeInTransaction: Starting manual transaction');

            $result = $callback();

            $connection->commit();
            $this->_loggerInterface->writeLog('executeInTransaction: Transaction committed successfully');

            return $result;

        } catch (\Exception $e) {
            $connection->rollBack();
            $this->_loggerInterface->writeLog('executeInTransaction: Error occurred - ' . $e->getMessage() . ' - Transaction rolled back');
            throw new LocalizedException(__('Transaction failed: %1', $e->getMessage()));
        }
    }

    /**
     * 利用訂單編號取出訂購商品
     * @param string $orderId
     * @return array    $results
     */
    public function getSalesOrderItemByOrderId($orderId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource      = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection    = $resource->getConnection();

        $select = $connection->select()->from(['soi' => 'sales_order_item'],
            ['name'])->where("soi.order_id = :order_id");

        $bind    = ['order_id' => $orderId];
        return $connection->fetchAll($select, $bind);
    }

    // invoice

    /**
     * 取得發票資訊
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoiceCarruerType($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceCarruerType();
    }

    /**
     * 取得發票資訊
     * @param string $orderId
     * @return string
     */
    public function getecpayInvoiceType($orderId)
    {
        return $this->getOrder($orderId)->getecpayInvoiceType();
    }

    /**
     * 取得發票資訊
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoiceCarruerNum($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceCarruerNum();
    }

    /**
     * 取得發票資訊
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoiceLoveCode($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceLoveCode();
    }

    /**
     * 取得發票資訊
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoiceCustomerIdentifier($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceCustomerIdentifier();
    }

    /**
     * 取得發票資訊(公司抬頭)
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoiceCustomerCompany($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceCustomerCompany();
    }

    /**
     * 取得發票開立旗標 0.未開立 1.已開立
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoiceTag($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceTag();
    }

    /**
     * 取得發票開立旗標 1.一般開立 2.延遲開立
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoiceIssueType($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceIssueType();
    }

    /**
     * 取得發票交易單號
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoiceOdSob($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceOdSob();
    }

    /**
     * 取得發票號碼
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoiceNumber($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceNumber();
    }

    /**
     * 取得發票開立時間
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoiceDate($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceDate();
    }

    /**
     * 取得是否列印
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoicePrint($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoicePrint();
    }

    /**
     * 取得列印列印次數
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoicePrintViews($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoicePrintViews();
    }

    /**
     * 取得發票最近更新時間
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoiceUpdatedAt($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceUpdatedAt();
    }

    /**
     * 取得發票隨機碼
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoiceRandomNumber($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceRandomNumber();
    }

    /**
     * 取得發票自動開立程序
     * @param string $orderId
     * @return string
     */
    public function getEcpayInvoiceAutoTag($orderId)
    {
        return $this->getOrder($orderId)->getEcpayInvoiceAutoTag();
    }

    /**
     * 利用商家自訂訂單編號找出訂單
     * @param string $invoiceOdSob
     * @return array    $results
     */
    public function getOrderByEcpayInvoiceOdSob($invoiceOdSob)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource      = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection    = $resource->getConnection();

        $select = $connection->select()->from(['so' => 'sales_order'],
            ['*'])->where("so.ecpay_invoice_od_sob = :ecpay_invoice_od_sob");

        $bind    = ['ecpay_invoice_od_sob' => $invoiceOdSob];
        return $connection->fetchAll($select, $bind);
    }

    /**
     * 執行Magento本身的開立發票程序
     * @param $orderId
     * @return void
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws Zend_Log_Exception
     */
    public function setOrderInvoice($orderId)
    {
        $this->_loggerInterface->writeLog('setOrderInvoice 執行Magento本身的發票程序');
        $order = $this->getOrder($orderId);

        /**
         * Urgently change invoice state to "paid".
         * Note: The `setCapture()` function is unexpectedly not working.
         * Due to the critical nature of this issue, we are forcibly updating the state
         * as a temporary workaround. Proper investigation is recommended.
         */
        if ($order->getInvoiceCollection()->getSize()) {
            $invoiceId = $order->getInvoiceCollection()->getLastItem()->getId();
            $invoice = $this->_invoiceRepository->get($invoiceId);
            $invoice->setState(Invoice::STATE_PAID);
            $this->_invoiceRepository->save($invoice);
            $this->setIsInProcess($orderId);
        }

        if ($order->canInvoice()) {
            $this->_loggerInterface->writeLog('setOrderInvoice 開始執行...');

            $baseGrandTotal = $this->getBaseGrandTotal($orderId);
            $grandTotal     = $this->getGrandTotal($orderId);

            // 使用批量更新提高效能
            $this->setOrderDataBatch($orderId, [
                'base_total_paid' => $baseGrandTotal,
                'total_paid' => $grandTotal
            ], true);

            $this->_loggerInterface->writeLog('setOrderInvoice base_total_paid:' . print_r($baseGrandTotal, true));
            $this->_loggerInterface->writeLog('setOrderInvoice total_paid:' . print_r($grandTotal, true));
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->getOrder()->setIsInProcess(true);
            $transactionSave = $this->_transaction->addObject($invoice)->addObject($invoice->getOrder())->save();
        }
    }

    // logistic
    /**
     * 取得物流方式
     * @param string $orderId
     * @return string
     */
    public function getShippingMethod($orderId)
    {
        return $this->getOrder($orderId)->getShippingMethod();
    }

    /**
     * 取得物流方式組合名稱
     * @param string $orderId
     * @return string
     */
    public function getShippingDescription($orderId)
    {
        return $this->getOrder($orderId)->getShippingDescription();
    }

    /**
     * 取得超商店舖編號
     * @param string $orderId
     * @return string
     */
    public function getEcpayLogisticCvsStoreId($orderId)
    {
        return $this->getOrder($orderId)->getEcpayLogisticCvsStoreId();
    }

    /**
     * 取得超商店舖名稱
     * @param string $orderId
     * @return string
     */
    public function getEcpayLogisticCvsStoreName($orderId)
    {
        return $this->getOrder($orderId)->getEcpayLogisticCvsStoreName();
    }

    /**
     * 取得超商店舖地址
     * @param string $orderId
     * @return string
     */
    public function getEcpayLogisticCvsStoreAddress($orderId)
    {
        return $this->getOrder($orderId)->getEcpayLogisticCvsStoreAddress();
    }

    /**
     * 取得超商店舖電話
     * @param string $orderId
     * @return string
     */
    public function getEcpayLogisticCvsStoreTelephone($orderId)
    {
        return $this->getOrder($orderId)->getEcpayLogisticCvsStoreTelephone();
    }

    /**
     * 取得物流單自動開立程序
     * @param string $orderId
     * @return string
     */
    public function getEcpayLogisticAutoTag($orderId)
    {
        return $this->getOrder($orderId)->getEcpayLogisticAutoTag();
    }

    /**
     * 取得綠界物流單建立旗標 0.未建立 1.已建立
     * @param string $orderId
     * @return string
     */
    public function getEcpayShippingTag($orderId)
    {
        return $this->getOrder($orderId)->getEcpayShippingTag();
    }

    /**
     * 取得綠界物流單資訊
     * @param string $orderId
     * @return string
     */
    public function getEcpayLogisticInfo($orderId)
    {
        $info = [];

        $ecpayLogisticModel = $this->_ecpayLogisticFactory->create();

        $collection = $ecpayLogisticModel->getCollection()->addFieldToFilter(
            'order_id',
            ['eq' => $orderId]
        )->setOrder('entity_id', 'DESC')->setCurPage(1)->setPageSize(1);

        foreach ($collection as $item) {
            $info = $item->getData();
        }

        return $info;
    }

    /**
     * 利用MerchantTradeNo取得綠界物流單資訊
     * @param string $MerchantTradeNo
     * @return string
     */
    public function getEcpayLogisticInfoByMerchantTradeNo($MerchantTradeNo)
    {
        $info = [];

        $ecpayLogisticModel = $this->_ecpayLogisticFactory->create();

        $collection = $ecpayLogisticModel->getCollection()->addFieldToFilter(
            'merchant_trade_no',
            ['eq' => $MerchantTradeNo]
        )->setOrder('entity_id', 'DESC')->setCurPage(1)->setPageSize(1);

        foreach ($collection as $item) {
            $info = $item->getData();
        }

        return $info;
    }

    /**
     * 執行Magento本身的物流程序
     * @param string $orderId
     * @return array    $results
     */
    public function setOrderShip($orderId)
    {
        $this->_loggerInterface->writeLog('setOrderShip 執行Magento本身的物流程序');

        $order = $this->getOrder($orderId);

        if (!$order->canShip()) {
            throw new LocalizedException(
                __("You can't create the Shipment of this order.")
            );
        }

        $orderShipment = $this->_convertOrder->toShipment($order);

        foreach ($order->getAllItems() as $orderItem) {
            // Check virtual item and item Quantity
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }

            $qty          = $orderItem->getQtyToShip();
            $shipmentItem = $this->_convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
            $orderShipment->addItem($shipmentItem);
        }

        $orderShipment->register();
        $orderShipment->getOrder()->setIsInProcess(true);

        try {
            // Save created Order Shipment
            $orderShipment->save();
            $orderShipment->getOrder()->save();

            // Send Shipment Email
            $this->_shipmentNotifier->notify($orderShipment);
            $orderShipment->save();
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * 取得Ship ID
     * @param string $orderId
     * @param string $key
     * @param string $value
     */
    public function getShipmentId($orderId)
    {
        $shipmentId = null;

        $order = $this->getOrder($orderId);

        $shipmentCollection = $order->getShipmentsCollection();

        foreach ($shipmentCollection as $shipment) {
            $shipmentId = $shipment->getIncrementId();
        }

        $this->_loggerInterface->writeLog('OrderService getShipmentId shipmentId:' . print_r($shipmentId, true));
        return $shipmentId;
    }

    // payment

    /**
     * 取得綠界金流資訊
     * @param string $orderId
     * @return string
     */
    public function getEcpayPaymentInfo($orderId)
    {
        $info = [];

        $ecpayPaymentInfoModel = $this->_ecpayPaymentInfoFactory->create();

        $collection = $ecpayPaymentInfoModel->getCollection()->addFieldToFilter(
            'order_id',
            ['eq' => $orderId]
        )->setOrder('entity_id', 'DESC')->setCurPage(1)->setPageSize(1);

        foreach ($collection as $item) {
            $info = $item->getData();
        }

        return $info;
    }

    /**
     * 取得綠界金流資訊內容
     * @param string $orderId
     * @param string $paymentMethod
     * @return array   $paymentInfo
     */
    public function getEcpayPaymentInfoContent($orderId, $paymentMethod)
    {
        $paymentInfo = [];

        // 繳費資訊
        $paymentInfoData = $this->getEcpayPaymentInfo($orderId);
        if (!empty($paymentInfoData)) {
            switch ($paymentMethod) {
                case 'ecpay_atm_gateway':
                    $paymentInfo = [
                        [
                            'key' => __('Bank code'),
                            'val' => $paymentInfoData['bank_code']
                        ],
                        [
                            'key' => __('ATM No'),
                            'val' => implode(' ', str_split($paymentInfoData['vaccount'], 4))
                        ],
                        [
                            'key' => __('Payment deadline'),
                            'val' => $paymentInfoData['expire_date']
                        ],
                    ];
                    break;
                case 'ecpay_cvs_gateway':
                    $paymentInfo = [
                        [
                            'key' => __('CVS No'),
                            'val' => $paymentInfoData['payment_no']
                        ],
                        [
                            'key' => __('Payment deadline'),
                            'val' => $paymentInfoData['expire_date']
                        ],
                    ];
                    break;
                case 'ecpay_barcode_gateway':
                    $paymentInfo = [
                        [
                            'key' => __('Barcode one'),
                            'val' => $paymentInfoData['barcode1']
                        ],
                        [
                            'key' => __('Barcode two'),
                            'val' => $paymentInfoData['barcode2']
                        ],
                        [
                            'key' => __('Barcode three'),
                            'val' => $paymentInfoData['barcode3']
                        ],
                        [
                            'key' => __('Payment deadline'),
                            'val' => $paymentInfoData['expire_date']
                        ],
                    ];
                    break;
            }
        }

        // 其他資訊
        $additionalInformation = $this->getAdditionalInformation($orderId);
        if (!empty($additionalInformation)) {
            switch ($paymentMethod) {
                case 'ecpay_credit_installment_gateway':
                    $creditInstallment = isset($additionalInformation['ecpay_credit_installment']) ? $this->_paymentService->getCreditInstallmentName(
                        $additionalInformation['ecpay_credit_installment']
                    ) : '';
                    $paymentInfo       = [
                        [
                            'key' => __('Credit installment'),
                            'val' => $creditInstallment
                        ],
                    ];
                    break;
            }
        }

        return $paymentInfo;
    }

    /**
     * 利用MerchantTradeNo取得綠界金流資訊
     * @param string $MerchantTradeNo
     * @return string
     */
    public function getEcpayPaymentInfoByMerchantTradeNo($MerchantTradeNo)
    {
        $info = [];

        $ecpayPaymentInfoModel = $this->_ecpayPaymentInfoFactory->create();

        $collection = $ecpayPaymentInfoModel->getCollection()->addFieldToFilter(
            'merchant_trade_no',
            ['eq' => $MerchantTradeNo]
        )->setOrder('entity_id', 'DESC')->setCurPage(1)->setPageSize(1);

        foreach ($collection as $item) {
            $info = $item->getData();
        }

        return $info;
    }

    /**
     * 取得付款完成狀態
     * @param string $orderId
     * @return string
     */
    public function getEcpayPaymentCompleteTag($orderId)
    {
        return $this->getOrder($orderId)->getEcpayPaymentCompleteTag();
    }
}
