<?php

namespace Branch8\HifiSalesReport\Helper;

use Branch8\HifiSalesReport\Helper\Common as CommonHelper;
use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord as SubRecordModel;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Branch8\Refund\Model\ResourceModel\SalesRefund\CollectionFactory as SalesRefundCollectionFactory;
use Branch8\CTBC\Helper\OrderStatus as CtbcHelper;
use Branch8\CTBC\Helper\Response\CurrentState as RefundDataState;
use Webkul\Marketplace\Helper\Data as MarketplaceDataHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\SalesRule\Model\RuleRepository;
use Ecpay\Invoice\Model\HotaiOrderInvoiceLogs;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs\CollectionFactory as OrderInvoiceLogCollectionFactory;
use Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface;
use Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface;
use Ecpay\Invoice\Model\HotaiOrderItemInvoiceLogs;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderItemInvoiceLogs\Collection as OrderItemInvoiceLogCollection;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderItemInvoiceLogs\CollectionFactory as OrderItemInvoiceLogCollectionFactory;

class ReportOld
{
    const PAYMENT_METHOD_CREDIT_CARD      = "信用卡";
    const PAYMENT_METHOD_ALL_POINT        = "全點數交易";
    const PAYMENT_METHOD_CONVENIENT_STORE = "超商收款";

    const PRODUCT_NAME_FOR_POINT       = "點數扣抵";
    const ACCOUNTING_GRADE_FOR_PRODUCT = "銷貨收入.點數商城";
    const ACCOUNTING_GRADE_FOR_POINT   = "廣告費.點數折抵";
    const HOTAI_MAIN_DEALER_NAME       = "和泰聯網股份有限公司";

    const INVOICE_STATUS_ISSUE      = 1;
    const INVOICE_STATUS_CANCEL     = 2;
    const INVOICE_STATUS_ALLOWANCES = 3;
    const INVOICE_STATUS_NO_INVOICE = 4;

    const INVOICE_STATUS_LABEL_ISSUE      = "發票開立";
    const INVOICE_STATUS_LABEL_CANCEL     = "發票作廢";
    const INVOICE_STATUS_LABEL_ALLOWANCES = "發票折讓";
    const INVOICE_STATUS_LABEL_NO_INVOICE = "不開發票";

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var ParentOrder */
    protected $parentOrder;

    /** @var SalesRefundCollectionFactory */
    protected $salesRefundCollectionFactory;

    /** @var CtbcHelper */
    protected $ctbcHelper;

    /** @var MarketplaceDataHelper */
    protected $marketplaceDataHelper;

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var RuleRepository */
    protected $ruleRepository;

    /** @var OrderInvoiceLogCollectionFactory */
    protected $orderInvoiceLogCollectionFactory;

    /** @var OrderItemInvoiceLogCollectionFactory */
    protected $orderItemInvoiceLogCollectionFactory;

    public $collectingMethodsAll;
    public $collectingMethodsCreditCard;
    public $collectingMethodsConvenientStore;

    public function __construct(
        CommonHelper $commonHelper,
        ParentOrder $parentOrder,
        SalesRefundCollectionFactory $salesRefundCollectionFactory,
        CtbcHelper $ctbcHelper,
        MarketplaceDataHelper $marketplaceDataHelper,
        CustomerRepositoryInterface $customerRepository,
        RuleRepository $ruleRepository,
        OrderInvoiceLogCollectionFactory $orderInvoiceLogCollectionFactory,
        OrderItemInvoiceLogCollectionFactory $orderItemInvoiceLogCollectionFactory
    ) {
        $this->commonHelper                         = $commonHelper;
        $this->parentOrder                          = $parentOrder;
        $this->salesRefundCollectionFactory         = $salesRefundCollectionFactory;
        $this->ctbcHelper                           = $ctbcHelper;
        $this->marketplaceDataHelper                = $marketplaceDataHelper;
        $this->customerRepository                   = $customerRepository;
        $this->ruleRepository                       = $ruleRepository;
        $this->orderInvoiceLogCollectionFactory     = $orderInvoiceLogCollectionFactory;
        $this->orderItemInvoiceLogCollectionFactory = $orderItemInvoiceLogCollectionFactory;

        $this->collectingMethodsCreditCard      = $this->commonHelper->getPaymentMethodsBelongToCreditCard();
        $this->collectingMethodsConvenientStore = $this->commonHelper->getPaymentMethodsBelongToConvenientStore();
        $this->collectingMethodsAll             = array_merge(
            $this->collectingMethodsCreditCard,
            $this->collectingMethodsConvenientStore
        );
    }

    /**
     * 根據傳入的order, 用orderId和ecpay_invoice_updated_at來指定搜尋order invoice log
     * @param \Magento\Sales\Model\Order $order
     * @return null|\Ecpay\Invoice\Model\HotaiOrderInvoiceLogs
     */
    public function getInvoiceLogByOrder(Order $order): null|HotaiOrderInvoiceLogs
    {
        $collection = $this->orderInvoiceLogCollectionFactory->create();
        $collection->addFieldToFilter(HotaiOrderInvoiceLogsInterface::ORDER_ID, (string) $order->getId());
        $collection->addFieldToFilter(HotaiOrderInvoiceLogsInterface::CREATED_AT, (string) $order->getData("ecpay_invoice_updated_at"));
        $result = $collection->getFirstItem();

        return empty($result->getId()) ? null : $result;
    }

    /**
     * 以order ID獲取最舊的order invoice log
     * @param int $orderId
     * @return null|\Ecpay\Invoice\Model\HotaiOrderInvoiceLogs
     */
    public function getOldestInvoiceLogByOrderId(int $orderId): null|HotaiOrderInvoiceLogs
    {
        $collection = $this->orderInvoiceLogCollectionFactory->create();
        $collection->addFieldToFilter(HotaiOrderInvoiceLogsInterface::ORDER_ID, (string) $orderId);
        $collection->addOrder(HotaiOrderInvoiceLogsInterface::CREATED_AT, 'ASC');
        $collection->addOrder(HotaiOrderInvoiceLogsInterface::HOTAI_ORDER_INVOICE_LOGS_ID, 'ASC');
        $result = $collection->getFirstItem();

        return empty($result->getId()) ? null : $result;
    }

    /**
     * 以order ID獲取最新的order invoice log
     * @param int $orderId
     * @return null|\Ecpay\Invoice\Model\HotaiOrderInvoiceLogs
     */
    public function getNewestInvoiceLogByOrderId(int $orderId): null|HotaiOrderInvoiceLogs
    {
        $collection = $this->orderInvoiceLogCollectionFactory->create();
        $collection->addFieldToFilter(HotaiOrderInvoiceLogsInterface::ORDER_ID, (string) $orderId);
        $collection->addOrder(HotaiOrderInvoiceLogsInterface::CREATED_AT, 'DESC');
        $collection->addOrder(HotaiOrderInvoiceLogsInterface::HOTAI_ORDER_INVOICE_LOGS_ID, 'DESC');
        $result = $collection->getFirstItem();

        return empty($result->getId()) ? null : $result;
    }

    public function getOrderItemInvoiceLogsByOrderInvoiceLog(HotaiOrderInvoiceLogs $orderInvoiceLog): OrderItemInvoiceLogCollection
    {
        $collection = $this->orderItemInvoiceLogCollectionFactory->create();
        $collection->addFieldToFilter(HotaiOrderItemInvoiceLogsInterface::HOTAI_ORDER_INVOICE_LOG_ID, (string) $orderInvoiceLog->getId());
        $result = $collection->load();

        return $result;
    }

    public function getNewestInvoiceLogByOrderItemIdAndType(int $orderItemId, string $type): null|HotaiOrderItemInvoiceLogs
    {
        $collection = $this->orderItemInvoiceLogCollectionFactory->create();
        $collection->addFieldToFilter(HotaiOrderItemInvoiceLogsInterface::ORDER_ITEM_ID, $orderItemId);
        $collection->addFieldToFilter(HotaiOrderItemInvoiceLogsInterface::TYPE, $type);
        $collection->addOrder(HotaiOrderItemInvoiceLogsInterface::CREATED_AT, 'DESC');
        $collection->addOrder(HotaiOrderItemInvoiceLogsInterface::HOTAI_ORDER_ITEM_INVOICE_LOGS_ID, 'DESC');
        $result = $collection->getFirstItem();

        return empty($result->getId()) ? null : $result;
    }

    /**
     * 獲取訂單結帳序號異動日
     * @param Order $order
     * @return string
     */
    public function getChangeDayTitle(Order $order): string
    {
        return date("Y-m-d", strtotime($order->getData("ecpay_invoice_updated_at")));
    }

    /**
     * 根據發票狀態獲取"訂單狀態"名稱
     * 根據主檔範例
     * (1) 發票開立 => 訂單成立
     * (2) 發票作廢 或 發票折讓 => 訂單取消
     * (3) 根據主檔範例, 不開發票也是對應到「訂單成立」
     * @param int $invoiceStatus
     * @return string
     */
    public function getStatusTitleByInvoiceStatus(int $invoiceStatus): string
    {
        switch ($invoiceStatus) {
            case self::INVOICE_STATUS_NO_INVOICE:
            case self::INVOICE_STATUS_ISSUE:
                return "訂單成立";

            case self::INVOICE_STATUS_CANCEL:
            case self::INVOICE_STATUS_ALLOWANCES:
                return "訂單取消";

            default:
                return "發票狀態({$invoiceStatus})對應訂單狀態失敗";
        }
    }

    /**
     * 確認order是否為"全點數交易"
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function checkIsOrderPaidByOnlyPoint(Order $order): bool
    {
        return !is_null($order->getGrandTotal()) && $order->getGrandTotal() == 0;
    }

    public function getPaymentTitleForInvoiceOrder(string $paymentMethod): string
    {
        if (!empty($this->collectingMethodsCreditCard) && in_array($paymentMethod, $this->collectingMethodsCreditCard)) {
            return self::PAYMENT_METHOD_CREDIT_CARD;
        }

        if (!empty($this->collectingMethodsConvenientStore) && in_array($paymentMethod, $this->collectingMethodsConvenientStore)) {
            return self::PAYMENT_METHOD_CONVENIENT_STORE;
        }

        return "付款方式對應失敗({$paymentMethod})";
    }

    public function getPaymentTitleForPurePointOrder(): string
    {
        return self::PAYMENT_METHOD_ALL_POINT;
    }

    public function getInvoiceStatusLabel(Order $order, int $invoiceStatus = 0): string
    {
        if ($this->checkIsOrderPaidByOnlyPoint($order) || $invoiceStatus == self::INVOICE_STATUS_NO_INVOICE) {
            return self::INVOICE_STATUS_LABEL_NO_INVOICE;
        }

        switch ($invoiceStatus) {
            case 1:
                return self::INVOICE_STATUS_LABEL_ISSUE;

            case 2:
                return self::INVOICE_STATUS_LABEL_CANCEL;

            case 3:
                return self::INVOICE_STATUS_LABEL_ALLOWANCES;

            case 4:
                return self::INVOICE_STATUS_LABEL_NO_INVOICE;

            default:
                return "Fail mapping: {$invoiceStatus}";
        }
    }

    /**
     * 根據order獲取買受人名稱
     * @param Order $order
     * @return string
     */
    public function getCustomerNameByOrder(Order $order): string
    {
        return $order->getCustomerLastname() . " " . $order->getCustomerFirstname();
    }

    public function getInvoiceWithTaxByOrder(Order $order): float
    {
        if ($this->checkIsOrderPaidByOnlyPoint($order)) {
            return 0;
        }

        return $order->getTotalInvoiced() ?? 0;
    }

    public function getInvoiceTaxByOrder(Order $order): float
    {
        if ($this->checkIsOrderPaidByOnlyPoint($order)) {
            return 0;
        }

        return $order->getTaxInvoiced() ?? 0;
    }

    public function getInvoiceWithoutTaxByOrder(Order $order): float
    {
        if ($this->checkIsOrderPaidByOnlyPoint($order)) {
            return 0;
        }

        if (is_null($order->getTotalInvoiced()) || is_null($order->getTaxInvoiced())) {
            return 0;
        }

        return $order->getTotalInvoiced() - $order->getTaxInvoiced();
    }

    public function getInvoiceWithTaxByOrderItem(OrderItem $orderItem): float
    {
        if (is_null($orderItem->getRowTotalInclTax()) || is_null($orderItem->getDiscountInvoiced())) {
            return 0;
        }

        return $orderItem->getRowTotalInclTax() - $orderItem->getDiscountInvoiced();
    }

    public function getInvoiceTaxByOrderItem(OrderItem $orderItem): float
    {
        return $orderItem->getTaxInvoiced() ?? 0;
    }

    public function getInvoiceWithoutTaxByOrderItem(OrderItem $orderItem): float
    {
        if (is_null($orderItem->getRowTotalInclTax()) || is_null($orderItem->getTaxInvoiced()) || is_null($orderItem->getDiscountInvoiced())) {
            return 0;
        }

        return $orderItem->getRowTotalInclTax() - $orderItem->getTaxInvoiced() - $orderItem->getDiscountInvoiced();
    }

    public function getInvoiceWithTaxPointValueByOrderItem(OrderItem $orderItem): int
    {
        return $orderItem->getData("row_total_point_used") ?? 0;
    }

    public function getInvoiceTaxPointValueByOrderItem(OrderItem $orderItem): float
    {
        $point                         = $this->getInvoiceWithTaxPointValueByOrderItem($orderItem);
        $invoiceWithoutTaxInPointValue = $this->getInvoiceWithoutTaxPointValueByOrderItem($orderItem);

        return $point - $invoiceWithoutTaxInPointValue;
    }

    public function getInvoiceWithoutTaxPointValueByOrderItem(OrderItem $orderItem): float
    {
        $point      = $this->getInvoiceWithTaxPointValueByOrderItem($orderItem);
        $taxPercent = (float) $orderItem->getTaxPercent();

        return round($point / (1 + $taxPercent / 100), 0);
    }

    public function getParentOrderIdByOrderId(int $orderId): int
    {
        return (int) $this->parentOrder->getParentOrder($orderId);
    }

    public function getRefundDataByOrderId(int $orderId): array
    {
        return $this->ctbcHelper->getRefundDataByOrderId($orderId);
    }

    public function getRefundLabelByOrderId(int $orderId): string
    {
        try {
            $refundData = $this->getRefundDataByOrderId($orderId);

            if (!isset($refundData["CurrentState"])) {
                return "CurrentState index not found - " . json_encode($refundData);
            }

            if (!isset(RefundDataState::LABEL[$refundData["CurrentState"]])) {
                return "CurrentState map fail - " . $refundData["CurrentState"];
            }

            return RefundDataState::LABEL[$refundData["CurrentState"]];
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 根據order item獲取帳務檔中的"售價"欄位內容
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return float
     */
    public function getSellingPriceByOrderItem(OrderItem $orderItem): float
    {
        return $orderItem->getOriginalPrice() ?? 0;
    }

    /**
     * 根據order item獲取帳務檔中的"聯網負擔折扣"欄位內容
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @return float
     */
    public function getUnityNetDiscountByOrderItem(OrderItem $orderItem): float
    {
        /** @var HotaiOrderItemInvoiceLogs $discountLog */
        $discountLog = $this->getNewestInvoiceLogByOrderItemIdAndType((int) $orderItem->getId(), HotaiOrderItemInvoiceLogs::TYPE_DISCOUNT);

        return is_null($discountLog) ? round(abs($orderItem->getDiscountAmount())) : $discountLog->getIncludeTax();
    }

    /**
     * 用order累加出三種tax相關的point值
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getInvoicePointsByOrder(Order $order): array
    {
        $result = [
            "withTax"    => 0,
            "tax"        => 0,
            "withoutTax" => 0
        ];

        $orderInvoiceLog = $this->getNewestInvoiceLogByOrderId((int) $order->getId());

        if (is_null($orderInvoiceLog)) {
            $collection = $order->getItems();
            foreach ($collection as $orderItem) {
                $result["withTax"] += $this->getInvoiceWithTaxPointValueByOrderItem($orderItem);
                $result["tax"] += $this->getInvoiceTaxPointValueByOrderItem($orderItem);
                $result["withoutTax"] += $this->getInvoiceWithoutTaxPointValueByOrderItem($orderItem);
            }

            return $result;
        }

        $collection = $this->getOrderItemInvoiceLogsByOrderInvoiceLog($orderInvoiceLog);
        /** @var HotaiOrderItemInvoiceLogs $orderItemInvoiceLog */
        foreach ($collection->getItems() as $orderItemInvoiceLog) {
            if ($orderItemInvoiceLog->getType() != HotaiOrderItemInvoiceLogs::TYPE_POINT) {
                continue;
            }

            $result["withTax"] += abs($orderItemInvoiceLog->getIncludeTax());
            $result["tax"] += abs($orderItemInvoiceLog->getTax());
            $result["withoutTax"] += abs($orderItemInvoiceLog->getExcludeTax());
        }

        return $result;
    }

    /**
     * 用order累加出三種tax相關的運費值
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function getInvoiceShippingByOrder(Order $order): array
    {
        $result = [
            "withTax"    => 0,
            "tax"        => 0,
            "withoutTax" => 0
        ];

        $orderInvoiceLog = $this->getNewestInvoiceLogByOrderId((int) $order->getId());

        if (is_null($orderInvoiceLog)) {
            $result["withTax"]    = round((float) $order->getShippingInclTax());
            $result["tax"]        = round((float) $order->getShippingTaxAmount());
            $result["withoutTax"] = round((float) $order->getShippingAmount());

            return $result;
        }

        $collection = $this->getOrderItemInvoiceLogsByOrderInvoiceLog($orderInvoiceLog);
        /** @var HotaiOrderItemInvoiceLogs $orderItemInvoiceLog */
        foreach ($collection->getItems() as $orderItemInvoiceLog) {
            if ($orderItemInvoiceLog->getType() != HotaiOrderItemInvoiceLogs::TYPE_SHIPPING) {
                continue;
            }

            $result["withTax"] += abs($orderItemInvoiceLog->getIncludeTax());
            $result["tax"] += abs($orderItemInvoiceLog->getTax());
            $result["withoutTax"] += abs($orderItemInvoiceLog->getExcludeTax());
        }

        return $result;
    }

    /**
     * 用order item回傳三種tax相關的"折扣價值"
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param null|\Ecpay\Invoice\Model\HotaiOrderInvoiceLogs $orderInvoiceLog
     * @return array
     */
    public function getInvoiceDiscountsByOrderItemAndOrderInvoiceLog(OrderItem $orderItem): array
    {
        $result = [
            "invoiceWithTaxDiscountValue"    => 0,
            "invoiceTaxDiscountValue"        => 0,
            "invoiceWithoutTaxDiscountValue" => 0
        ];

        $orderInvoiceLog = $this->getNewestInvoiceLogByOrderId((int) $orderItem->getOrderId());

        if (is_null($orderInvoiceLog)) {
            $discountInclTax = round(abs($orderItem->getDiscountAmount()));
            $discountExclTax = round($discountInclTax / 1.05);
            $discountTax     = $discountInclTax - $discountExclTax;

            $result["invoiceWithTaxDiscountValue"]    = $discountInclTax;
            $result["invoiceTaxDiscountValue"]        = $discountTax;
            $result["invoiceWithoutTaxDiscountValue"] = $discountExclTax;

            return $result;
        }

        /** @var HotaiOrderItemInvoiceLogs $discountLog */
        $discountLog               = $this->getNewestInvoiceLogByOrderItemIdAndType((int) $orderItem->getId(), HotaiOrderItemInvoiceLogs::TYPE_DISCOUNT);
        $invoiceWithTaxDiscount    = is_null($discountLog) ? 0 : abs($discountLog->getIncludeTax());
        $invoiceTaxDiscount        = is_null($discountLog) ? 0 : abs($discountLog->getTax());
        $invoiceWithoutTaxDiscount = is_null($discountLog) ? 0 : abs($discountLog->getExcludeTax());


        $result["invoiceWithTaxDiscountValue"]    = $invoiceWithTaxDiscount;
        $result["invoiceTaxDiscountValue"]        = $invoiceTaxDiscount;
        $result["invoiceWithoutTaxDiscountValue"] = $invoiceWithoutTaxDiscount;

        return $result;
    }

    /**
     * 用order item回傳三種tax相關的"點數價值"
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param null|\Ecpay\Invoice\Model\HotaiOrderInvoiceLogs $orderInvoiceLog
     * @return array
     */
    public function getInvoicePointsByOrderItemAndOrderInvoiceLog(OrderItem $orderItem): array
    {
        $result = [
            "invoiceWithTaxPointValue"    => 0,
            "invoiceTaxPointValue"        => 0,
            "invoiceWithoutTaxPointValue" => 0
        ];

        $orderInvoiceLog = $this->getNewestInvoiceLogByOrderId((int) $orderItem->getOrderId());

        if (is_null($orderInvoiceLog)) {
            $result["invoiceWithTaxPointValue"]    = $this->getInvoiceWithTaxPointValueByOrderItem($orderItem);
            $result["invoiceTaxPointValue"]        = $this->getInvoiceTaxPointValueByOrderItem($orderItem);
            $result["invoiceWithoutTaxPointValue"] = $this->getInvoiceWithoutTaxPointValueByOrderItem($orderItem);

            return $result;
        }

        /** @var HotaiOrderItemInvoiceLogs $pointLog */
        $pointLog               = $this->getNewestInvoiceLogByOrderItemIdAndType((int) $orderItem->getId(), HotaiOrderItemInvoiceLogs::TYPE_POINT);
        $invoiceWithTaxPoint    = is_null($pointLog) ? 0 : abs($pointLog->getIncludeTax());
        $invoiceTaxPoint        = is_null($pointLog) ? 0 : abs($pointLog->getTax());
        $invoiceWithoutTaxPoint = is_null($pointLog) ? 0 : abs($pointLog->getExcludeTax());


        $result["invoiceWithTaxPointValue"]    = $invoiceWithTaxPoint;
        $result["invoiceTaxPointValue"]        = $invoiceTaxPoint;
        $result["invoiceWithoutTaxPointValue"] = $invoiceWithoutTaxPoint;

        return $result;
    }

    /**
     * 用order item回傳三種tax相關的"整體價值"(金錢價值+點數價值)
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param null|\Ecpay\Invoice\Model\HotaiOrderInvoiceLogs $orderInvoiceLog
     * @return array
     */
    public function getInvoiceValuesByOrderItemAndOrderInvoiceLog(OrderItem $orderItem): array
    {
        $result = [
            "invoiceWithTaxTotalValue"    => 0,
            "invoiceTaxTotalValue"        => 0,
            "invoiceWithoutTaxTotalValue" => 0
        ];

        $orderInvoiceLog = $this->getNewestInvoiceLogByOrderId((int) $orderItem->getOrderId());

        if (is_null($orderInvoiceLog)) {
            $discountInclTax = round(abs($orderItem->getDiscountAmount()));
            $discountExclTax = round($discountInclTax / 1.05);
            $discountTax     = $discountInclTax - $discountExclTax;

            $result["invoiceWithTaxTotalValue"]    = $this->getInvoiceWithTaxPointValueByOrderItem($orderItem) + $discountInclTax;
            $result["invoiceTaxTotalValue"]        = $this->getInvoiceTaxPointValueByOrderItem($orderItem) + $discountTax;
            $result["invoiceWithoutTaxTotalValue"] = $this->getInvoiceWithoutTaxPointValueByOrderItem($orderItem) + $discountExclTax;

            return $result;
        }

        /** @var HotaiOrderItemInvoiceLogs $moneyLog */
        $moneyLog               = $this->getNewestInvoiceLogByOrderItemIdAndType((int) $orderItem->getId(), HotaiOrderItemInvoiceLogs::TYPE_ITEM);
        $invoiceWithTaxMoney    = is_null($moneyLog) ? 0 : abs($moneyLog->getIncludeTax() * $moneyLog->getQty());
        $invoiceTaxMoney        = is_null($moneyLog) ? 0 : abs($moneyLog->getTax() * $moneyLog->getQty());
        $invoiceWithoutTaxMoney = is_null($moneyLog) ? 0 : abs($moneyLog->getExcludeTax() * $moneyLog->getQty());

        $result["invoiceWithTaxTotalValue"]    = $invoiceWithTaxMoney;
        $result["invoiceTaxTotalValue"]        = $invoiceTaxMoney;
        $result["invoiceWithoutTaxTotalValue"] = $invoiceWithoutTaxMoney;

        return $result;
    }

    /**
     * 判斷報表是否要求根據發票狀態反轉數字(乘上負號)
     * @param string|int $invoiceStatus
     * @return bool
     */
    public function needToReverseNumberForInvoiceStatus(string|int $invoiceStatus): bool
    {
        if ($invoiceStatus == self::INVOICE_STATUS_NO_INVOICE) {
            return false;
        }

        if ($invoiceStatus == self::INVOICE_STATUS_ISSUE) {
            return false;
        }

        return true;
    }

    public function getLogisticStatus(): string
    {
        return "物流狀態(階段二的任務, 需要確定從哪個欄位獲取這項資訊)";
    }

    public function getDiscountFromDealer(): string
    {
        return "廠商負擔折扣(階段二任務, 未來要能記錄兩種折扣規則各自折抵了多少錢)";
    }

    /**
     * 取得報表要用的"發票日期"欄位
     * 1 發票開立/作廢 = 發票日期
     * 2 發票折退 = 原始發票開立日
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getInvoiceDateForReportByOrder(Order $order): string
    {
        $invoiceStatus = $order->getData("ecpay_invoice_status");

        switch ($invoiceStatus) {
            case self::INVOICE_STATUS_NO_INVOICE:
                return "";

            case self::INVOICE_STATUS_ISSUE:
            case self::INVOICE_STATUS_CANCEL:
                return $this->getChangeDayTitle($order);

            case self::INVOICE_STATUS_ALLOWANCES:
                /** @var HotaiOrderInvoiceLogs $orderInvoiceLog */
                $orderInvoiceLog = $this->getOldestInvoiceLogByOrderId((int) $order->getId());
                return $orderInvoiceLog->getCreatedAt();

            default:
                return "訂單狀態({$invoiceStatus})對應發票日期失敗";
        }
    }

    /**
     * 取得報表要用的"折讓單日期"欄位
     * 1 發票開立/作廢 = "1900/01/01"
     * 2 發票折退 = 發票異動日
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getAllowancesDateForReportByOrder(Order $order): string
    {
        $invoiceStatus = $order->getData("ecpay_invoice_status");

        switch ($invoiceStatus) {
            case self::INVOICE_STATUS_NO_INVOICE:
                return "";

            case self::INVOICE_STATUS_ISSUE:
            case self::INVOICE_STATUS_CANCEL:
                return "1900/01/01";

            case self::INVOICE_STATUS_ALLOWANCES:
                return $this->getChangeDayTitle($order);

            default:
                return "訂單狀態({$invoiceStatus})對應折讓單日期失敗";
        }
    }

    /**
     * 獲取HIFI同步時發票狀態欄位的對應數字代號
     * 1: 開立(發票開立)
     * 2: 作廢(發票作廢)
     * 4: 四聯退回(發票折讓, 不開發票)
     * @param integer $invoiceStatus
     * @return integer
     */
    public function getInvoiceStatusCodeForHifiSync(int $invoiceStatus): int
    {
        switch ($invoiceStatus) {
            case SubRecordModel::INVOICE_STATUS_ISSUE:
                return 1;

            case SubRecordModel::INVOICE_STATUS_CANCEL:
                return 2;

            case SubRecordModel::INVOICE_STATUS_ALLOWANCES:
            case SubRecordModel::INVOICE_STATUS_NO_INVOICE:
                return 4;

            default:
                return 0;
        }
    }

    /**
     * 根據給定order產出銷貨介面主檔ID
     * ID共10位
     * sales_order.entity_id左方補零至9個字
     * 發票開立補上前綴"1"
     * 其他狀態補上前綴"2"
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function generateSalesInterfaceId(Order $order): string
    {
        $invoiceStatus = $order->getData("ecpay_invoice_status");

        $prefix = ((int) $invoiceStatus == self::INVOICE_STATUS_ISSUE) ? "1" : "2";

        return $prefix . str_pad((string) $order->getId(), 9, '0', STR_PAD_LEFT);
    }

    /**
     * 組出HIFI需要的折讓單號
     * 取子訂單編號前13碼+後2碼
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getInvoiceAllowancesNumber(Order $order): string
    {
        if ($order->getData("ecpay_invoice_status") == self::INVOICE_STATUS_ISSUE) {
            return "";
        }

        $target = $order->getData("hotai_child_order_number");

        $prefix = substr($target, 0, 13);
        $suffix = substr($target, -2);

        return $prefix . $suffix;
    }
}
