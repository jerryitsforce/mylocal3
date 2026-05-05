<?php

namespace Branch8\HifiSalesReport\Helper;

use Branch8\HifiSalesReport\Helper\Common as CommonHelper;
use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord as SubRecordModel;
use Magento\CatalogRule\Model\Rule;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Branch8\Refund\Model\ResourceModel\SalesRefund\CollectionFactory as SalesRefundCollectionFactory;
use Branch8\CTBC\Helper\OrderStatus as CtbcHelper;
use Branch8\CTBC\Helper\Response\CurrentState as RefundDataState;
use Webkul\Marketplace\Helper\Data as MarketplaceDataHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\SalesRule\Model\RuleRepository;
use Magento\CatalogRule\Model\CatalogRuleRepository;
use Ecpay\Invoice\Model\HotaiOrderInvoiceLogs;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs\CollectionFactory as OrderInvoiceLogCollectionFactory;
use Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface;
use Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface;
use Ecpay\Invoice\Model\HotaiOrderItemInvoiceLogs;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderItemInvoiceLogs\Collection as OrderItemInvoiceLogCollection;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderItemInvoiceLogs\CollectionFactory as OrderItemInvoiceLogCollectionFactory;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\CatalogRule\Model\ResourceModel\Rule\Collection as RuleCollection;

class Report
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

    /** @var CatalogRuleRepository */
    protected $catalogRuleRepository;

    /** @var OrderInvoiceLogCollectionFactory */
    protected $orderInvoiceLogCollectionFactory;

    /** @var OrderItemInvoiceLogCollectionFactory */
    protected $orderItemInvoiceLogCollectionFactory;

    /** @var RuleCollectionFactory */
    protected $ruleCollectionFactory;

    public $collectingMethodsAll;
    public $collectingMethodsCreditCard;
    public $collectingMethodsConvenientStore;
    public $itemDescStringLengthLimit;

    public function __construct(
        CommonHelper $commonHelper,
        ParentOrder $parentOrder,
        SalesRefundCollectionFactory $salesRefundCollectionFactory,
        CtbcHelper $ctbcHelper,
        MarketplaceDataHelper $marketplaceDataHelper,
        CustomerRepositoryInterface $customerRepository,
        RuleRepository $ruleRepository,
        CatalogRuleRepository $catalogRuleRepository,
        OrderInvoiceLogCollectionFactory $orderInvoiceLogCollectionFactory,
        OrderItemInvoiceLogCollectionFactory $orderItemInvoiceLogCollectionFactory,
        RuleCollectionFactory $ruleCollectionFactory
    ) {
        $this->commonHelper                         = $commonHelper;
        $this->parentOrder                          = $parentOrder;
        $this->salesRefundCollectionFactory         = $salesRefundCollectionFactory;
        $this->ctbcHelper                           = $ctbcHelper;
        $this->marketplaceDataHelper                = $marketplaceDataHelper;
        $this->customerRepository                   = $customerRepository;
        $this->ruleRepository                       = $ruleRepository;
        $this->catalogRuleRepository                = $catalogRuleRepository;
        $this->orderInvoiceLogCollectionFactory     = $orderInvoiceLogCollectionFactory;
        $this->orderItemInvoiceLogCollectionFactory = $orderItemInvoiceLogCollectionFactory;
        $this->ruleCollectionFactory                = $ruleCollectionFactory;

        $this->collectingMethodsCreditCard      = $this->commonHelper->getPaymentMethodsBelongToCreditCard();
        $this->collectingMethodsConvenientStore = $this->commonHelper->getPaymentMethodsBelongToConvenientStore();
        $this->collectingMethodsAll             = array_merge(
            $this->collectingMethodsCreditCard,
            $this->collectingMethodsConvenientStore
        );

        $this->itemDescStringLengthLimit = $this->commonHelper->getItemDescStringLengthLimit();
    }

    /**
     * 判斷報表是否要求根據發票狀態反轉數字(乘上負號)
     * @param string|int $invoiceStatus
     * @return bool
     */
    public function needToReverseNumberForInvoiceStatus(string|int $invoiceStatus, string|int $isReverse): bool
    {
        if ($invoiceStatus == self::INVOICE_STATUS_NO_INVOICE && $isReverse == 0) {
            return false;
        }

        if ($invoiceStatus == self::INVOICE_STATUS_ISSUE) {
            return false;
        }

        return true;
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

    /**
     * 獲取訂單結帳序號異動日
     * @param Order $order
     * @return string
     */
    public function getChangeDayTitle(string $ecpayInvoiceUpdatedAt): string
    {
        $dateObj   = new \DateTime();
        $timestamp = strtotime($ecpayInvoiceUpdatedAt . " " . CommonHelper::TIMEZONE);
        $dateObj->setTimezone(new \DateTimeZone(CommonHelper::TIMEZONE));
        $dateObj->setTimestamp($timestamp);

        return $dateObj->format("Y-m-d");
    }

    /**
     * 根據發票狀態獲取"訂單狀態"名稱
     * 正流程 => 訂單成立
     * 逆流成 => 訂單取消
     * @param int $isReverse
     * @return string
     */
    public function getStatusTitleByIsReverse(int $isReverse): string
    {
        return $isReverse ? "訂單取消" : "訂單成立";
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

    public function getInvoiceStatusLabel(int $invoiceStatus): string
    {
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
     * 產出"商品項目"欄位
     * HIFI同步API接收此欄位字數上限為100
     *
     * @param string $orderItemName
     * @param null|string $productOptionsJsonString
     * @return string
     */
    public function getProductDetail(string $orderItemName, null|string $productOptionsJsonString): string
    {
        if (is_null($productOptionsJsonString)) {
            return mb_substr($orderItemName, 0, $this->itemDescStringLengthLimit, 'UTF-8');
        }

        $productOptionsArray = json_decode($productOptionsJsonString, true);

        if (!isset($productOptionsArray["options"])) {
            return mb_substr($orderItemName, 0, $this->itemDescStringLengthLimit, 'UTF-8');
        }

        $append = "";
        foreach ($productOptionsArray["options"] as $option) {
            if (isset($option["print_value"])) {
                $append .= "|" . $option["print_value"];
                continue;
            }

            if (isset($option["value"])) {
                $append .= "|" . $option["value"];
                continue;
            }
        }

        return mb_substr($orderItemName . $append, 0, $this->itemDescStringLengthLimit, 'UTF-8');
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

    /**
     * 根據order獲取買受人名稱
     * @param Order $order
     * @return string
     */
    public function getCustomerNameByOrder(Order $order): string
    {
        return $order->getCustomerLastname() . " " . $order->getCustomerFirstname();
    }

    /**
     * 取得報表要用的"發票日期"欄位
     * 1 發票開立/作廢 = 發票日期
     * 2 發票折退 = 原始發票開立日
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getInvoiceDateForReportByOrder(int $ecpayInvoiceStatus, string $ecpayInvoiceUpdatedAt, int $orderId): string
    {
        switch ($ecpayInvoiceStatus) {
            case self::INVOICE_STATUS_ISSUE:
            case self::INVOICE_STATUS_CANCEL:
            case self::INVOICE_STATUS_NO_INVOICE:
                return $this->getChangeDayTitle($ecpayInvoiceUpdatedAt);

            case self::INVOICE_STATUS_ALLOWANCES:
                /** @var HotaiOrderInvoiceLogs $orderInvoiceLog */
                $orderInvoiceLog = $this->getOldestInvoiceLogByOrderId($orderId);
                $createdAt = $orderInvoiceLog->getCreatedAt();
                return $this->getChangeDayTitle($createdAt);

            default:
                return "訂單狀態({$ecpayInvoiceStatus})對應發票日期失敗";
        }
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
     * 取得報表要用的"折讓單日期"欄位
     * 1 發票開立/作廢 = "1900/01/01"
     * 2 發票折退 = 發票異動日
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getAllowancesDateForReportByOrder(int $ecpayInvoiceStatus, int $isReverse, string $ecpayInvoiceUpdatedAt): string
    {
        switch ($ecpayInvoiceStatus) {
            case self::INVOICE_STATUS_ISSUE:
            case self::INVOICE_STATUS_CANCEL:
                return "1900/01/01";

            case self::INVOICE_STATUS_ALLOWANCES:
                return $this->getChangeDayTitle($ecpayInvoiceUpdatedAt);

            case self::INVOICE_STATUS_NO_INVOICE:
                return $isReverse == 0 ? "1900/01/01" : $this->getChangeDayTitle($ecpayInvoiceUpdatedAt);

            default:
                return "訂單狀態({$ecpayInvoiceStatus})對應折讓單日期失敗";
        }
    }

    /**
     * ID共10位
     *   2.0的sales_order.entity_id左方補零至7個字
     *   正流程補上前綴"1"
     *   逆流程補上前綴"2"
     *   第1位數是流程前綴
     *   第2,3位數是重開次數(第1次重開是01)
     *   範例(orderId:9527, 第一次重開正流程)
     *   1010009527
     *
     * @param int $isReverse
     * @param int $orderId
     * @param int $invoiceCount
     * @return string
     */
    public function generateSalesInterfaceId(int $isReverse, int $orderId, int $invoiceCount): string
    {
        $prefix          = ((int) $isReverse == 0) ? "1" : "2";
        $padInvoiceCount = str_pad($invoiceCount, 2, '0', STR_PAD_LEFT);

        return $prefix . $padInvoiceCount . str_pad((string) $orderId, 7, '0', STR_PAD_LEFT);
    }

    /**
     * 獲取HIFI同步時發票狀態欄位的對應數字代號
     * @param int $invoiceStatus
     * @param int $isReverse
     * @return int
     */
    public function getInvoiceStatusCodeForHifiSync(int $invoiceStatus, int $isReverse, int $isCrossMonth): int
    {
        // TODO 這地方邏輯可以精簡
        switch ($invoiceStatus) {
            case SubRecordModel::INVOICE_STATUS_ISSUE:
                return 1;

            case SubRecordModel::INVOICE_STATUS_CANCEL:
                return 2;

            case SubRecordModel::INVOICE_STATUS_ALLOWANCES:
                return 4;

            case SubRecordModel::INVOICE_STATUS_NO_INVOICE:
                if ($isReverse != 0 && $isCrossMonth == 0) {
                    return 2;
                }

                if ($isReverse != 0 && $isCrossMonth == 1) {
                    return 4;
                }

                return 1;

            default:
                return 0;
        }
    }

    /**
     * 獲取HIFI同步時"收入類別"欄位的對應代號
     * "U": 發票開立, 發票作廢, 發票折讓
     * "U1": 不開發票
     * @param integer $invoiceStatus
     * @return integer
     */
    public function getRevenueType(int $invoiceStatus): string
    {
        switch ($invoiceStatus) {
            case SubRecordModel::INVOICE_STATUS_ISSUE:
            case SubRecordModel::INVOICE_STATUS_CANCEL:
            case SubRecordModel::INVOICE_STATUS_ALLOWANCES:
                return "U";

            case SubRecordModel::INVOICE_STATUS_NO_INVOICE:
                return "U1";

            default:
                return "";
        }
    }

    /**
     * 組出HIFI需要的折讓單號
     * 取子訂單編號前13碼+後2碼
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getInvoiceAllowancesNumber(int $ecpayInvoiceStatus, int $isReverse, string $hotaiChildOrderNumber, int $isCrossMonth): string
    {
        // TODO 這地方邏輯可以精簡
        if ($this->getInvoiceStatusCodeForHifiSync($ecpayInvoiceStatus, $isReverse, $isCrossMonth) == 1) {
            return "";
        }

        $prefix = substr($hotaiChildOrderNumber, 0, 13);
        $suffix = substr($hotaiChildOrderNumber, -2);

        return $prefix . $suffix;
    }

    public function getLogisticStatus(): string
    {
        return "物流狀態(階段二的任務, 需要確定從哪個欄位獲取這項資訊)";
    }

    public function getParentOrderIdByOrderId(int $orderId): int
    {
        return (int) $this->parentOrder->getParentOrder($orderId);
    }

    public function getRefundDataByOrderId(int $orderId): array
    {
        return $this->ctbcHelper->getRefundDataByOrderId($orderId);
    }

    // TODO 應該改成先確認是否為0元母訂單, 如果不是0元才有必要查詢退款狀態
    // app/code/Branch8/CTBC/Model/OrderManagement::getParentOrder
    // app/code/Branch8/CTBC/Model/OrderManagement::getOrderPurchAmt
    public function getRefundLabelByOrderId(int $orderId): string
    {
        try {
            $refundData = $this->getRefundDataByOrderId($orderId);

            // 這代表母訂單整體0元會回傳的錯誤
            // example: {"RespCode":"9","SwRevision":"MicroQuery Server 3.2 (2019/10/25)","QueryCode":"-1","ErrStatus":"9","ERRDESC":"PurchAmt 必需大於零","VERSION":"3.2","QueryError":"(9:nt) PurchAmt 必需大於零","ErrCode":"nt","ApiVersion":"2.9","ApiSwRevision":"PosApi for PG6(PHP) 2.9 date: 2023/11/02"}
            if (isset($refundData["RespCode"]) && $refundData["RespCode"] == 9) {
                return "";
            }

            if (!isset($refundData["CurrentState"])) {
                return "CurrentState index not found - " . json_encode($refundData);
            }

            if (!isset(RefundDataState::CHINESE_LABEL[$refundData["CurrentState"]])) {
                return "CurrentState map fail - " . $refundData["CurrentState"];
            }

            return RefundDataState::CHINESE_LABEL[$refundData["CurrentState"]];
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    /**
     * @param $appiledRulesJson
     * @return Rule[]
     */
    public function getCatalogPriceRulesCollection($appiledRulesJson): array
    {
        try {
            $appiledRules = json_decode($appiledRulesJson, true);

            $rules = [];
            foreach ($appiledRules as $rule) {
                $rules[] = $this->catalogRuleRepository->get($rule["rule_id"]);
            }

            return $rules;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getRuleCollection(OrderItemInvoiceLogCollection $itemLogCollectionWithPriceLog): ?RuleCollection
    {
        $ruleIds = [];

        foreach ($itemLogCollectionWithPriceLog as $itemLog) {
            if (is_null($itemLog->getData("price_log"))) {
                continue;
            }

            $appiledRules = json_decode($itemLog->getData("price_log"), true);

            if (empty($appiledRules)) {
                continue;
            }

            foreach ($appiledRules as $rule) {
                if (!isset($rule["rule_id"])) {
                    continue;
                }

                $ruleIds[] = $rule["rule_id"];
            }
        }

        if (count($ruleIds) == 0) {
            return null;
        }

        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->addFieldToSelect(['name']);
        $ruleCollection->addFieldToFilter('rule_id', ['in' => $ruleIds]);

        return $ruleCollection;
    }

    public function getCatalogPriceRuleDiscount(HotaiOrderItemInvoiceLogs $itemLogWithSalesItemData): float
    {
        $discount = 0;

        if (empty($itemLogWithSalesItemData->getData("price_log"))) {
            return $discount;
        }

        $priceLog = $itemLogWithSalesItemData->getData("price_log");

        if (empty($priceLog)) {
            return $discount;
        }

        $appiledRuleData = json_decode($priceLog, true);

        if (empty($appiledRuleData)) {
            return $discount;
        }

        // rule: https://trello.com/c/5P85D1iA

        $priceInclTax  = $itemLogWithSalesItemData->getData('price_incl_tax');
        $originalPrice = $itemLogWithSalesItemData->getData('original_price');
        $specialPrice  = $itemLogWithSalesItemData->getData('special_price');

        $discount = ($specialPrice ?? $originalPrice) - $priceInclTax;

        return round($discount);
    }

    public function getCatalogPriceRuleName(HotaiOrderItemInvoiceLogs $itemLogWithSalesItemData, null|RuleCollection $ruleCollection): string
    {
        $appiledRuleNamesArray = [];

        if (empty($itemLogWithSalesItemData->getData("price_log"))) {
            return implode(",", $appiledRuleNamesArray);
        }

        $priceLog = $itemLogWithSalesItemData->getData("price_log");

        if (empty($priceLog)) {
            return implode(",", $appiledRuleNamesArray);
        }

        $appiledRuleData = json_decode($priceLog, true);

        if (empty($appiledRuleData)) {
            return implode(",", $appiledRuleNamesArray);
        }

        foreach ($appiledRuleData as $rule) {
            if (is_null($ruleCollection)) {
                $appliedRuleNamesArray[] = "ruleCollection為null";
                continue;
            }

            if (!isset($rule["rule_id"])) {
                $appliedRuleNamesArray[] = "找不到rule_id欄位";
                continue;
            }

            $ruleObj = $ruleCollection->getItemById($rule["rule_id"]);

            if (is_null($ruleObj)) {
                $appliedRuleNamesArray[] = "找不到對應rule名稱(rule_id: {$rule["rule_id"]})";
                continue;
            }

            $appliedRuleNamesArray[] = $ruleObj->getName();
        }

        return implode(",", $appliedRuleNamesArray);
    }

    public function getOrderIdFromSalesInterfaceId(string $salesInterfaceId): int
    {
        return (int) substr($salesInterfaceId, 3);
    }
}
