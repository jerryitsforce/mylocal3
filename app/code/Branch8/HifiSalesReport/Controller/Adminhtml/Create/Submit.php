<?php

declare(strict_types=1);

namespace Branch8\HifiSalesReport\Controller\Adminhtml\Create;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecordFactory;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecord;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecordRepository;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord as SubRecord;
use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecordFactory as SubRecordFactory;
use Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportSubRecord\CollectionFactory as SubRecordCollectionFactory;
use Magento\Framework\DB\Transaction;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Branch8\HifiSalesReport\Helper\Report as ReportHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs\CollectionFactory as OrderLogCollectionFactory;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderItemInvoiceLogs\CollectionFactory as ItemLogCollectionFactory;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderItemInvoiceLogs\Collection as ItemLogCollection;
use Ecpay\Invoice\Model\HotaiOrderItemInvoiceLogs;
use Magento\CatalogRule\Model\ResourceModel\Rule\Collection as RuleCollection;

class Submit extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    const MAIN_FILE_CONTENT_HEADER                  = [
        "訂單結帳序號異動日",
        "訂單編號",
        "訂單狀態(財務專用)",
        "付款方式",
        "發票狀態",
        "開立發票編號",
        "訂單結帳序號",
        "買受人統編",
        "買受人名稱",
        "發票_含稅",
        "發票_稅金",
        "發票_未稅",
        "退款狀態",
        "發票日期",
        "折讓單日期",
        "銷貨介面主檔ID",
        "發票處理區分",
        "折讓退回流水號",
        "收入類別"
    ];
    const MAIN_FILE_INDEX_INVOICE_CHANGE_DATE       = 0; // 訂單結帳序號異動日
    const MAIN_FILE_INDEX_CHILD_ORDER_NUMBER        = 1; // 訂單編號
    const MAIN_FILE_INDEX_ORDER_STATUS_TITLE        = 2; // 訂單狀態(財務專用)
    const MAIN_FILE_INDEX_PAYMENT_TITLE             = 3; // 付款方式
    const MAIN_FILE_INDEX_INVOICE_STATUS            = 4; // 發票狀態
    const MAIN_FILE_INDEX_INVOICE_NUMBER            = 5; // 開立發票編號
    const MAIN_FILE_INDEX_CHECKOUT_NUMBER           = 6; // 訂單結帳序號
    const MAIN_FILE_INDEX_CUSTOMER_IDENTIFIER       = 7; // 買受人統編
    const MAIN_FILE_INDEX_CUSTOMER_NAME             = 8; // 買受人名稱
    const MAIN_FILE_INDEX_INVOICE_INCL_TAX          = 9; // 發票_含稅
    const MAIN_FILE_INDEX_INVOICE_TAX               = 10; // 發票_稅金
    const MAIN_FILE_INDEX_INVOICE_EXCL_TAX          = 11; // 發票_未稅
    const MAIN_FILE_INDEX_REFUND_STATE              = 12; // 退款狀態
    const MAIN_FILE_INDEX_INVOICE_DATE              = 13; // 發票日期
    const MAIN_FILE_INDEX_ALLOWANCES_DATE           = 14; // 折讓單日期
    const MAIN_FILE_INDEX_SALES_INTERFACE_ID        = 15; // 銷貨介面主檔ID
    const MAIN_FILE_INDEX_INVOICE_STATUS_HIFI_CODE  = 16; // 發票處理區分
    const MAIN_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER = 17; // 折讓退回流水號
    const MAIN_FILE_INDEX_REVENUE_TYPE              = 18; // 收入類別
    const MAIN_FILE_INDEX_CUSTOM_IS_REVERSE         = 19; // 自訂正逆流程(排序完刪除)

    const DETAIL_FILE_CONTENT_HEADER                    = [
        "訂單結帳序號異動日",
        "子訂單編號",
        "發票狀態",
        "發票編號",
        "訂單結帳序號",
        "結帳項次",
        "商品編號",
        "商品項目",
        "發票_含稅",
        "發票_稅金",
        "發票_未稅",
        "物流狀態",
        "HIFI拋帳會科設定",
        "門市名稱",
        "廠商售價",
        "廠商負擔-購物車折扣",
        "聯網負擔-購物車折扣",
        "購物車折扣名稱",
        "活動折扣",
        "活動折扣名稱",
        "銷貨介面主檔ID",
        "發票處理區分",
        "折讓單號",
        "點數中心_特約商交易序號",
        "數量",
        "商品小計",
    ];
    const DETAIL_FILE_INDEX_INVOICE_CHANGE_DATE         = 0; // 訂單結帳序號異動日
    const DETAIL_FILE_INDEX_CHILD_ORDER_NUMBER          = 1; // 子訂單編號
    const DETAIL_FILE_INDEX_INVOICE_STATUS              = 2; // 發票狀態
    const DETAIL_FILE_INDEX_INVOICE_NUMBER              = 3; // 發票編號
    const DETAIL_FILE_INDEX_CHECKOUT_NUMBER             = 4; // 訂單結帳序號
    const DETAIL_FILE_INDEX_CHECKOUT_COUNTER            = 5; // 結帳項次
    const DETAIL_FILE_INDEX_SKU                         = 6; // 商品編號
    const DETAIL_FILE_INDEX_PRODUCT_DETAIL              = 7; // 商品項目
    const DETAIL_FILE_INDEX_INVOICE_INCL_TAX            = 8; // 發票_含稅
    const DETAIL_FILE_INDEX_INVOICE_TAX                 = 9; // 發票_稅金
    const DETAIL_FILE_INDEX_INVOICE_EXCL_TAX            = 10; // 發票_未稅
    const DETAIL_FILE_INDEX_LOGISTIC_STATUS             = 11; // 物流狀態
    const DETAIL_FILE_INDEX_ACCOUNTING_GRADE            = 12; // HIFI拋帳會科設定
    const DETAIL_FILE_INDEX_SELLER_NAME                 = 13; // 特約商
    const DETAIL_FILE_INDEX_SELLING_PRICE               = 14; // 廠商售價
    const DETAIL_FILE_INDEX_DEALER_DISCOUNT             = 15; // 廠商負擔-購物車折扣
    const DETAIL_FILE_INDEX_UNITY_NET_DISCOUNT          = 16; // 聯網負擔-購物車折扣
    const DETAIL_FILE_INDEX_MARKETING_ACTIVITY          = 17; // 購物車折扣名稱
    const DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_DISCOUNT = 18; // 活動折扣
    const DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_NAME     = 19; // 活動折扣名稱
    const DETAIL_FILE_INDEX_SALES_INTERFACE_ID          = 20; // 銷貨介面主檔ID
    const DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE    = 21; // 發票處理區分
    const DETAIL_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER   = 22; // 折讓單號
    const DETAIL_FILE_INDEX_TRANS_SN                    = 23; // 點數中心_特約商交易序號
    const DETAIL_FILE_INDEX_QTY                         = 24; // 數量
    const DETAIL_FILE_INDEX_SUBTOTAL                    = 25; // 商品小計
    const DETAIL_FILE_INDEX_CUSTOM_SORT_VALUE           = 26; // 自定排序權重(排序完刪除)

    const PRODUCT_NAME_FOR_POINT        = "點數扣抵";
    const PRODUCT_NAME_FOR_DISCOUNT     = "活動折扣";
    const PRODUCT_NAME_FOR_SHIPPING     = "運費";
    const ACCOUNTING_GRADE_FOR_PRODUCT  = "銷貨收入.點數商城";
    const ACCOUNTING_GRADE_FOR_POINT    = "廣告費.點數折抵";
    const ACCOUNTING_GRADE_FOR_DISCOUNT = "銷貨收入.點數商城";
    const HOTAI_MAIN_DEALER_NAME        = "和泰聯網股份有限公司";

    /** @var PageFactory */
    protected $resultPageFactory;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var OrderCollectionFactory */
    protected $orderCollectionFactory;

    /** @var OrderItemCollectionFactory */
    protected $orderItemCollectionFactory;

    /** @var HifiSalesReportRecordFactory */
    protected $hifiSalesReportRecordFactory;

    /** @var HifiSalesReportRecordRepository */
    protected $hifiSalesReportRecordRepository;

    /** @var SubRecordFactory */
    protected $subRecordFactory;

    /** @var SubRecordCollectionFactory */
    protected $subRecordCollectionFactory;

    /** @var Transaction */
    protected $transaction;

    /** @var Filesystem */
    protected $filesystem;

    /** @var DirectoryList */
    protected $directoryList;

    /** @var \Magento\Framework\Filesystem\Directory\WriteInterface */
    protected $directory;

    /** @var AuthSession */
    protected $authSession;

    /** @var MessageManager */
    protected $messageManager;

    /** @var ReportHelper */
    protected $reportHelper;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var OrderItemRepositoryInterface */
    protected $orderItemRepository;

    /** @var OrderLogCollectionFactory */
    protected $orderLogCollectionFactory;

    /** @var ItemLogCollectionFactory */
    protected $itemLogCollectionFactory;

    protected $params;
    protected $queryStartTime;
    protected $queryStartTimeObj;
    protected $queryEndTime;
    protected $queryEndTimeObj;
    protected $collectingMethods;
    protected $orderCollection;
    protected $orderItemCollection;
    protected $ruleCollection;

    protected $originalMainFileName;
    protected $modifiedMainFileName;
    protected $originalDetailFileName;
    protected $modifiedDetailFileName;
    protected $refundStateCache;

    protected $allOrderIds               = [];
    protected $allOrderLogIdsBeforeGroup = [];
    protected $unityNetDiscountCache     = [];
    protected $purePointOrderIds         = [];

    protected $mainFileData     = [];
    protected $detailFileData   = [];
    protected $moneyDiffMessage = "";

    protected $handleDiffMainArray   = [];
    protected $handleDiffDetailArray = [];

    public function __construct(
        PageFactory $resultPageFactory,
        CommonHelper $commonHelper,
        OrderCollectionFactory $orderCollectionFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        HifiSalesReportRecordFactory $hifiSalesReportRecordFactory,
        HifiSalesReportRecordRepository $hifiSalesReportRecordRepository,
        SubRecordFactory $subRecordFactory,
        SubRecordCollectionFactory $subRecordCollectionFactory,
        Transaction $transaction,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        AuthSession $authSession,
        MessageManager $messageManager,
        ReportHelper $reportHelper,
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        OrderLogCollectionFactory $orderLogCollectionFactory,
        ItemLogCollectionFactory $itemLogCollectionFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->resultPageFactory               = $resultPageFactory;
        $this->commonHelper                    = $commonHelper;
        $this->orderCollectionFactory          = $orderCollectionFactory;
        $this->orderItemCollectionFactory      = $orderItemCollectionFactory;
        $this->hifiSalesReportRecordFactory    = $hifiSalesReportRecordFactory;
        $this->hifiSalesReportRecordRepository = $hifiSalesReportRecordRepository;
        $this->subRecordFactory                = $subRecordFactory;
        $this->subRecordCollectionFactory      = $subRecordCollectionFactory;
        $this->transaction                     = $transaction;
        $this->filesystem                      = $filesystem;
        $this->directoryList                   = $directoryList;
        $this->directory                       = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->authSession                     = $authSession;
        $this->messageManager                  = $messageManager;
        $this->reportHelper                    = $reportHelper;
        $this->orderRepository                 = $orderRepository;
        $this->orderItemRepository             = $orderItemRepository;
        $this->orderLogCollectionFactory       = $orderLogCollectionFactory;
        $this->itemLogCollectionFactory        = $itemLogCollectionFactory;

        $this->refundStateCache = [];

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $this->initParameters();

            if (!$this->checkStartTimeAndEndTime()) {
                $this->messageManager->addError(__("Invoice change end date must be greater than invoice change start date."));
                return $this->returnToListingPage();
            }

            if (!$this->checkInvoiceDateDoesNotOverlapWithExistingRecords()) {
                $this->messageManager->addError(__("Invoice change date can't overlap with existing records."));
                return $this->returnToListingPage();
            }

            $logCollection      = $this->getLogCollectionForMainFileData();
            $this->mainFileData = array_merge($this->mainFileData, $this->fillMainFileDataWithLogCollection($logCollection));

            $logCollection        = $this->getLogCollectionForDetailFileData();
            $this->detailFileData = array_merge($this->detailFileData, $this->fillDetailFileDataWithItemLogCollection($logCollection));

            // 根據前台傳入參數決定要不要使用調整尾數功能
            // $this->handleMoneyDiffIfRequested();

            // 新: 根據前台傳入參數決定要不要使用調整尾數功能-----------------
            $handleDiff = $this->_request->getParam('handle_diff');

            // 明細檔自身含稅與稅金的對應調整
            if (!empty($handleDiff) && $handleDiff == 1) {
                $this->adjustDetailDataBetweenInclTaxAndTax();
            }

            // 主檔與明細檔的對應調整
            if (!empty($handleDiff) && $handleDiff == 1) {
                $checkMismatchArray = [];
                $handledDetailArray = [];

                foreach ($this->handleDiffMainArray as $key => $mainData) {
                    // 主檔的紀錄對應不到明細檔紀錄, 不應該發生的異常狀況
                    if (!isset($this->handleDiffDetailArray[$key])) {
                        $checkMismatchArray[] = "key: {$key}";
                    }
                }

                if (count($checkMismatchArray) > 0) {
                    $this->messageManager->addError("處理稅差過程中, 部分主檔紀錄對應不到明細檔紀錄.");
                    $this->messageManager->addError("key值組成: invoice_number-order_id-invoice_status-is_reverse");
                    $this->messageManager->addError(json_encode($checkMismatchArray, JSON_UNESCAPED_UNICODE));
                    return $this->returnToListingPage();
                }

                foreach ($this->handleDiffMainArray as $key => $mainData) {
                    $detailData = $this->handleDiffDetailArray[$key];

                    $resultDetailData = $this->handleMoneyDiff($mainData, $detailData);

                    foreach ($resultDetailData as $resultDetailRow) {
                        $handledDetailArray[] = $resultDetailRow;
                    }
                }

                $this->detailFileData = $handledDetailArray;
            }
            // -----------------------------------------------------------

            $this->sortMainFileData();
            $this->sortDetailFileData();

            $this->addHeaderAndSummaryToMainFileData();
            $this->addHeaderAndSummaryToDetailFileData();

            $this->originalMainFileName   = $this->createOriginalMainFile();
            $this->modifiedMainFileName   = $this->createModifiedMainFile();
            $this->originalDetailFileName = $this->createOriginalDetailFile();
            $this->modifiedDetailFileName = $this->createModifiedDetailFile();

            $mainRecordId = $this->createSalesReportRecord();
            $this->createSalesReportSubRecord($mainRecordId);

            return $this->returnToListingPage();
        } catch (\Throwable $e) {
            // Use throwable here because exception won't handle typeError for function input.
            $this->messageManager->addError("發生例外錯誤");
            $this->messageManager->addError($e->getMessage());

            return $this->returnCreatePage();
        }
    }

    /**
     * 參數初始化
     * @return void
     */
    protected function initParameters(): void
    {
        $this->params         = $this->_request->getParams();
        $this->queryStartTime = $this->getStartTimeDateObj(false)->format("Y-m-d H:i:s");
        $this->queryEndTime   = $this->getEndTimeDateObj(false)->format("Y-m-d H:i:s");

        switch ($this->params[HifiSalesReportRecord::COLLECTING_METHOD]) {
            case HifiSalesReportRecord::COLLECTING_METHOD_CREDIT:
                $this->collectingMethods = $this->reportHelper->collectingMethodsCreditCard;
                break;

            case HifiSalesReportRecord::COLLECTING_METHOD_CONVENIENT_STORE:
                $this->collectingMethods = $this->reportHelper->collectingMethodsConvenientStore;
                break;

            case HifiSalesReportRecord::COLLECTING_METHOD_ALL:
            default:
                $this->collectingMethods = $this->reportHelper->collectingMethodsAll;
                break;
        }
    }

    /**
     * 檢查傳入的發票異動起訖日
     * @return bool
     */
    protected function checkStartTimeAndEndTime(): bool
    {
        return $this->getStartTimeDateObj()->getTimestamp() < $this->getEndTimeDateObj()->getTimestamp();
    }

    /**
     * 檢查欲創建紀錄的發票變動日沒有和已經存在的紀錄重疊
     * @return bool
     */
    protected function checkInvoiceDateDoesNotOverlapWithExistingRecords(): bool
    {
        $dateArray  = $this->getDateArrayForSubRecords();
        $collection = $this->subRecordCollectionFactory->create();
        $collection->addFieldToFilter(SubRecord::INVOICE_CHANGE_DATE, ['in' => $dateArray]);
        $collection->load();

        return count($collection->getItems()) == 0;
    }

    protected function getLogCollectionForMainFileData()
    {
        $collection = $this->orderLogCollectionFactory->create();
        $collection
            ->join(
                ['sales_order' => 'sales_order'],
                'main_table.order_id = sales_order.entity_id',
                [
                    'ecpay_invoice_status',
                    'ecpay_invoice_updated_at',
                    'hotai_child_order_number',
                    'grand_total',
                    'ecpay_invoice_number',
                    'ecpay_invoice_customer_identifier',
                    'has_refund',
                    'ecpay_invoice_customer_company'
                ]
            )->join(
                ['sales_order_payment' => 'sales_order_payment'],
                'sales_order.entity_id = sales_order_payment.parent_id',
                ['method']
            );

        $collection
            ->addFieldToFilter(
                'main_table.created_at',
                ['gteq' => $this->queryStartTime]
            )
            ->addFieldToFilter(
                'main_table.created_at',
                ['lteq' => $this->queryEndTime]
            )->addFieldToFilter(
                'sales_order_payment.method',
                ['in' => $this->collectingMethods]
            );
        $collection->load();

        foreach ($collection->getItems() as $invoice) {
            $this->allOrderIds[$invoice->getOrderId()] = $invoice->getOrderId();
            $this->allOrderLogIdsBeforeGroup[]         = $invoice->getHotaiOrderInvoiceLogsId();
        }

        return $collection;
    }

    protected function fillMainFileDataWithLogCollection($logCollection): array
    {
        $returnArray = [];

        foreach ($logCollection->getItems() as $invoiceLog) {
            $orderLogId        = (int) $invoiceLog->getHotaiOrderInvoiceLogsId();
            $orderId           = (int) $invoiceLog->getOrderId();
            $invoiceStatus     = (int) $invoiceLog->getStatus();
            $isReverse         = (int) $invoiceLog->getIsReverse();
            $isCrossMonth      = (int) $invoiceLog->getIsCrossMonth();
            $invoiceNumber     = $invoiceLog->getInvoiceNumber();
            $invoiceWithTax    = abs((int) $invoiceLog->getIncludeTax());
            $invoiceTax        = abs((int) $invoiceLog->getTax());
            $invoiceWithoutTax = abs((int) $invoiceLog->getExcludeTax());
            $paymentMethod     = $invoiceLog->getData('method');
            $hasRefund         = (int) $invoiceLog->getData('has_refund');
            $customerCompany   = $invoiceLog->getData('ecpay_invoice_customer_company');
            $invoiceCount      = (int) ($invoiceLog->getInvoiceCount() ?? 0);

            $handleDiffMainKey = "{$orderLogId}-{$invoiceNumber}-{$orderId}-{$invoiceStatus}-{$isReverse}-{$isCrossMonth}";

            if ($this->reportHelper->needToReverseNumberForInvoiceStatus($invoiceStatus, $isReverse)) {
                $invoiceWithTax *= -1;
                $invoiceTax *= -1;
                $invoiceWithoutTax *= -1;
            }

            $csvData                                                  = [];
            $csvData[self::MAIN_FILE_INDEX_INVOICE_CHANGE_DATE]       = $this->reportHelper->getChangeDayTitle($invoiceLog->getCreatedAt()); // 訂單結帳序號異動日
            $csvData[self::MAIN_FILE_INDEX_CHILD_ORDER_NUMBER]        = $invoiceLog->getData("hotai_child_order_number"); // 訂單編號
            $csvData[self::MAIN_FILE_INDEX_ORDER_STATUS_TITLE]        = $this->reportHelper->getStatusTitleByIsReverse((int) $isReverse); // 訂單狀態
            $csvData[self::MAIN_FILE_INDEX_PAYMENT_TITLE]             = $this->reportHelper->getPaymentTitleForInvoiceOrder($paymentMethod); // 付款方式
            $csvData[self::MAIN_FILE_INDEX_INVOICE_STATUS]            = $this->reportHelper->getInvoiceStatusLabel((int) $invoiceStatus); // 發票狀態
            $csvData[self::MAIN_FILE_INDEX_INVOICE_NUMBER]            = $invoiceLog->getInvoiceNumber(); // 開立發票編號
            $csvData[self::MAIN_FILE_INDEX_CHECKOUT_NUMBER]           = $invoiceLog->getData("hotai_checkout_number"); // 訂單結帳序號
            $csvData[self::MAIN_FILE_INDEX_CUSTOMER_IDENTIFIER]       = $invoiceLog->getData("ecpay_invoice_customer_identifier"); // 買受人統編
            $csvData[self::MAIN_FILE_INDEX_CUSTOMER_NAME]             = $customerCompany; // 買受人名稱
            $csvData[self::MAIN_FILE_INDEX_INVOICE_INCL_TAX]          = $invoiceWithTax; // 發票_含稅
            $csvData[self::MAIN_FILE_INDEX_INVOICE_TAX]               = $invoiceTax; // 發票_稅金
            $csvData[self::MAIN_FILE_INDEX_INVOICE_EXCL_TAX]          = $invoiceWithoutTax; // 發票_未稅
            $csvData[self::MAIN_FILE_INDEX_REFUND_STATE]              = $this->getRefundStateLabelByOrder($hasRefund, $orderId); // 退款狀態
            $csvData[self::MAIN_FILE_INDEX_INVOICE_DATE]              = $this->reportHelper->getInvoiceDateForReportByOrder($invoiceStatus, $invoiceLog->getCreatedAt(), (int) $orderId); // 發票日期
            $csvData[self::MAIN_FILE_INDEX_ALLOWANCES_DATE]           = $this->reportHelper->getAllowancesDateForReportByOrder($invoiceStatus, $isReverse, $invoiceLog->getCreatedAt()); // 折讓單日期
            $csvData[self::MAIN_FILE_INDEX_SALES_INTERFACE_ID]        = $this->reportHelper->generateSalesInterfaceId($isReverse, $orderId, $invoiceCount); // 銷貨介面主檔ID
            $csvData[self::MAIN_FILE_INDEX_INVOICE_STATUS_HIFI_CODE]  = $this->reportHelper->getInvoiceStatusCodeForHifiSync((int) $invoiceStatus, $isReverse, $isCrossMonth); // 發票處理區分
            $csvData[self::MAIN_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER] = $this->reportHelper->getInvoiceAllowancesNumber($invoiceStatus, $isReverse, $invoiceLog->getData("hotai_child_order_number"), $isCrossMonth); // 折讓退回流水號
            $csvData[self::MAIN_FILE_INDEX_REVENUE_TYPE]              = $this->reportHelper->getRevenueType((int) $invoiceStatus); // 收入類別
            $csvData[self::MAIN_FILE_INDEX_CUSTOM_IS_REVERSE]         = $isReverse; // 自訂正逆流程(排序完刪除)
            $returnArray[]                                            = $csvData;

            if (!isset($this->handleDiffMainArray[$handleDiffMainKey])) {
                $this->handleDiffMainArray[$handleDiffMainKey] = [];
            }
            $this->handleDiffMainArray[$handleDiffMainKey][] = $csvData;
        }

        return $returnArray;
    }

    protected function getLogCollectionForDetailFileData()
    {
        $collection = $this->itemLogCollectionFactory->create();

        $collection
            ->join(
                ['ecpay_invoice_hotai_order_invoice_logs' => 'ecpay_invoice_hotai_order_invoice_logs'],
                'main_table.hotai_order_invoice_log_id = ecpay_invoice_hotai_order_invoice_logs.	hotai_order_invoice_logs_id ',
                [
                    'hotai_order_invoice_logs_id' => 'hotai_order_invoice_logs_id',
                    'invoice_number'              => 'invoice_number',
                    'hotai_checkout_number'       => 'hotai_checkout_number',
                    'order_id'                    => 'order_id',
                    'status'                      => 'status',
                    'is_reverse'                  => 'is_reverse',
                    'is_cross_month'              => 'is_cross_month',
                    'ecpay_invoice_updated_at'    => 'created_at',
                    'invoice_count'               => 'invoice_count',
                    'seller_shop_name'            => 'seller_shop_name'
                ]
            )->join(
                ['sales_order' => 'sales_order'],
                'ecpay_invoice_hotai_order_invoice_logs.order_id = sales_order.entity_id',
                [
                    'ecpay_invoice_status',
                    'hotai_child_order_number',
                    'grand_total',
                    'ecpay_invoice_number',
                    'ecpay_invoice_customer_identifier',
                    'has_refund',
                    'ecpay_invoice_customer_company',
                ]
            );

        $collection->getSelect()->joinLeft(
            ['sales_order_item' => 'sales_order_item'],
            'main_table.order_item_id = sales_order_item.item_id',
            [
                'sku',
                'product_options',
                'seller_company_name',
                'original_price',
                'price_incl_tax',
                'special_price',
                'applied_rule_names',
                'price_log',
                'hotai_point_deduction_point_trans_s_n',
                'qty_ordered',
                'seller_borne_total_amount',
                'platform_borne_total_amount',
            ]
        );

        $collection->addFieldToFilter(
            'ecpay_invoice_hotai_order_invoice_logs.hotai_order_invoice_logs_id',
            ['in' => $this->allOrderLogIdsBeforeGroup]
        );

        $collection->addOrder('hotai_order_invoice_log_id', 'ASC');

        foreach ($collection->getItems() as $itemLog) {
            if ($itemLog->getType() != HotaiOrderItemInvoiceLogs::TYPE_DISCOUNT) {
                continue;
            }

            $this->unityNetDiscountCache[$itemLog->getHotaiOrderItemInvoiceLogsId()] = $itemLog->getIncludeTax();
        }

        $this->ruleCollection = $this->reportHelper->getRuleCollection($collection);

        return $collection;
    }

    protected function fillDetailFileDataWithItemLogCollection($logCollection)
    {
        $returnArray     = [];
        $preOrderLogId   = 0;
        $checkoutCounter = 1;
        /** @var HotaiOrderItemInvoiceLogs $itemLog */
        foreach ($logCollection->getItems() as $itemLog) {
            $orderId               = (int) $itemLog->getData('order_id');
            $orderLogId            = (int) $itemLog->getHotaiOrderInvoiceLogId();
            $invoiceStatus         = (int) $itemLog->getData("status");
            $isReverse             = (int) $itemLog->getData("is_reverse");
            $isCrossMonth          = (int) $itemLog->getData("is_cross_month");
            $invoiceNumber         = $itemLog->getData("invoice_number");
            $ecpayInvoiceUpdatedAt = $itemLog->getData('ecpay_invoice_updated_at');
            $itemLogType           = $itemLog->getType();
            $hotaiChildOrderNumber = $itemLog->getData('hotai_child_order_number');
            $originalPrice         = $itemLog->getData('original_price');
            $specialPrice          = $itemLog->getData('special_price');
            $invoiceCount          = (int) ($itemLog->getData('invoice_count') ?? 0);
            $transSn               = $itemLog->getData('hotai_point_deduction_point_trans_s_n') ?? '';
            $qty                   = $itemLog->getData('qty_ordered');  // 數量
            $catalogPriceRuleName  = $this->reportHelper->getCatalogPriceRuleName($itemLog, $this->ruleCollection); // 活動折扣名稱

            $invoiceProcessedCode     = $this->reportHelper->getInvoiceStatusCodeForHifiSync((int) $invoiceStatus, $isReverse, $isCrossMonth);
            $sellingPrice             = abs((int) ($specialPrice ?? $originalPrice)); // 廠商售價
            $sellerBorneTotalAmount   = abs((float) $itemLog->getData('seller_borne_total_amount')); // 廠商負擔-購物車折扣
            $platformBorneTotalAmount = abs((float) $itemLog->getData('platform_borne_total_amount')); // 聯網負擔-購物車折扣
            $catalogPriceRuleDiscount = abs((float) $this->reportHelper->getCatalogPriceRuleDiscount($itemLog) * $qty); // 活動折扣
            $subtotal                 = abs((int) ($specialPrice ?? $originalPrice) * $qty);  // 商品小計

            $handleDiffDetailKey = "{$orderLogId}-{$invoiceNumber}-{$orderId}-{$invoiceStatus}-{$isReverse}-{$isCrossMonth}";

            if ($preOrderLogId != $orderLogId) {
                $checkoutCounter = 1;
            }

            switch ($itemLogType) {
                case HotaiOrderItemInvoiceLogs::TYPE_ITEM:
                    $includeTax = (int) abs(round((float) $itemLog->getIncludeTax() * (int) $itemLog->getQty()));
                    $tax = (int) abs(round((float) $itemLog->getTax() * (int) $itemLog->getQty()));
                    $excludeTax = (int) abs(round((float) $itemLog->getExcludeTax() * (int) $itemLog->getQty()));

                    if ($this->reportHelper->needToReverseNumberForInvoiceStatus($invoiceStatus, $isReverse)) {
                        $includeTax *= -1;
                        $tax *= -1;
                        $excludeTax *= -1;
                        $sellingPrice *= -1;
                        $sellerBorneTotalAmount *= -1;
                        $platformBorneTotalAmount *= -1;
                        $catalogPriceRuleDiscount *= -1;
                        $subtotal *= -1;
                    }

                    $csvData = [];
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_CHANGE_DATE] = $this->reportHelper->getChangeDayTitle($ecpayInvoiceUpdatedAt); // 訂單結帳序號異動日
                    $csvData[self::DETAIL_FILE_INDEX_CHILD_ORDER_NUMBER] = $itemLog->getData("hotai_child_order_number"); // 子訂單編號
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS] = $this->reportHelper->getInvoiceStatusLabel($invoiceStatus); // 發票狀態
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_NUMBER] = $invoiceNumber; // 發票編號
                    $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_NUMBER] = $itemLog->getData("hotai_checkout_number"); // 訂單結帳序號
                    $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_COUNTER] = $checkoutCounter++; // 結帳項次
                    $csvData[self::DETAIL_FILE_INDEX_SKU] = $itemLog->getData('sku'); // 商品編號
                    $csvData[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] = $this->reportHelper->getProductDetail($itemLog->getOrderItemName(), $itemLog->getData("product_options")); // 商品項目
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX] = $includeTax; // 發票_含稅
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_TAX] = $tax; // 發票_稅金
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] = $excludeTax; // 發票_未稅
                    $csvData[self::DETAIL_FILE_INDEX_LOGISTIC_STATUS] = $this->reportHelper->getLogisticStatus(); // 物流狀態
                    $csvData[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] = self::ACCOUNTING_GRADE_FOR_PRODUCT; // HIFI拋帳會科設定
                    $csvData[self::DETAIL_FILE_INDEX_SELLER_NAME] = $itemLog->getData("seller_shop_name"); // 特約商
                    $csvData[self::DETAIL_FILE_INDEX_SELLING_PRICE] = $sellingPrice; // 廠商售價
                    $csvData[self::DETAIL_FILE_INDEX_DEALER_DISCOUNT] = $sellerBorneTotalAmount; // 廠商負擔-購物車折扣
                    $csvData[self::DETAIL_FILE_INDEX_UNITY_NET_DISCOUNT] = $platformBorneTotalAmount; // 聯網負擔-購物車折扣
                    $csvData[self::DETAIL_FILE_INDEX_MARKETING_ACTIVITY] = $itemLog->getData("applied_rule_names"); // 購物車折扣名稱
                    $csvData[self::DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_DISCOUNT] = $catalogPriceRuleDiscount; // 活動折扣
                    $csvData[self::DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_NAME] = $catalogPriceRuleName; // 活動折扣名稱
                    $csvData[self::DETAIL_FILE_INDEX_SALES_INTERFACE_ID] = $this->reportHelper->generateSalesInterfaceId($isReverse, $orderId, $invoiceCount); // 銷貨介面主檔ID
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE] = $invoiceProcessedCode; // 發票處理區分
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER] = $this->reportHelper->getInvoiceAllowancesNumber($invoiceStatus, $isReverse, $hotaiChildOrderNumber, $isCrossMonth); // 折讓單號
                    $csvData[self::DETAIL_FILE_INDEX_TRANS_SN] = $transSn; // 點數中心_特約商交易序號
                    $csvData[self::DETAIL_FILE_INDEX_QTY] = $qty; // 數量
                    $csvData[self::DETAIL_FILE_INDEX_SUBTOTAL] = $subtotal; // 商品小計
                    $csvData[self::DETAIL_FILE_INDEX_CUSTOM_SORT_VALUE] = $this->getCustomSortValueForDetailFileData($ecpayInvoiceUpdatedAt, $orderId, $invoiceStatus, $checkoutCounter); // 自定排序權重(排序完刪除)

                    $returnArray[] = $csvData;
                    break;

                case HotaiOrderItemInvoiceLogs::TYPE_POINT:
                    $includeTax = (int) abs(round((float) $itemLog->getIncludeTax()));
                    $tax = (int) abs(round((float) $itemLog->getTax()));
                    $excludeTax = (int) abs(round((float) $itemLog->getExcludeTax()));

                    $includeTax *= -1;
                    $tax *= -1;
                    $excludeTax *= -1;
                    // $sellerBorneTotalAmount *= -1;
                    // $platformBorneTotalAmount *= -1;

                    if ($this->reportHelper->needToReverseNumberForInvoiceStatus($invoiceStatus, $isReverse)) {
                        $includeTax *= -1;
                        $tax *= -1;
                        $excludeTax *= -1;
                        // $sellerBorneTotalAmount *= -1;
                        // $platformBorneTotalAmount *= -1;
                    }

                    $csvData = [];
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_CHANGE_DATE] = $this->reportHelper->getChangeDayTitle($ecpayInvoiceUpdatedAt); // 訂單結帳序號異動日
                    $csvData[self::DETAIL_FILE_INDEX_CHILD_ORDER_NUMBER] = $itemLog->getData("hotai_child_order_number"); // 子訂單編號
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS] = $this->reportHelper->getInvoiceStatusLabel($invoiceStatus); // 發票狀態
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_NUMBER] = $invoiceNumber; // 發票編號
                    $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_NUMBER] = $itemLog->getData("hotai_checkout_number"); // 訂單結帳序號
                    $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_COUNTER] = $checkoutCounter++; // 結帳項次
                    $csvData[self::DETAIL_FILE_INDEX_SKU] = ""; // 商品編號
                    $csvData[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] = self::PRODUCT_NAME_FOR_POINT; // 商品項目
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX] = $includeTax; // 發票_含稅
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_TAX] = $tax; // 發票_稅金
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] = $excludeTax; // 發票_未稅
                    $csvData[self::DETAIL_FILE_INDEX_LOGISTIC_STATUS] = ""; // 物流狀態
                    $csvData[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] = self::ACCOUNTING_GRADE_FOR_POINT; // HIFI拋帳會科設定
                    $csvData[self::DETAIL_FILE_INDEX_SELLER_NAME] = self::HOTAI_MAIN_DEALER_NAME; // 特約商
                    $csvData[self::DETAIL_FILE_INDEX_SELLING_PRICE] = ""; // 廠商售價
                    $csvData[self::DETAIL_FILE_INDEX_DEALER_DISCOUNT] = ""; // 廠商負擔-購物車折扣
                    $csvData[self::DETAIL_FILE_INDEX_UNITY_NET_DISCOUNT] = ""; // 聯網負擔-購物車折扣
                    $csvData[self::DETAIL_FILE_INDEX_MARKETING_ACTIVITY] = ""; // 購物車折扣名稱
                    $csvData[self::DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_DISCOUNT] = ""; // 活動折扣
                    $csvData[self::DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_NAME] = ""; // 活動折扣名稱
                    $csvData[self::DETAIL_FILE_INDEX_SALES_INTERFACE_ID] = $this->reportHelper->generateSalesInterfaceId($isReverse, $orderId, $invoiceCount); // 銷貨介面主檔ID
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE] = $invoiceProcessedCode; // 發票處理區分
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER] = $this->reportHelper->getInvoiceAllowancesNumber($invoiceStatus, $isReverse, $hotaiChildOrderNumber, $isCrossMonth); // 折讓單號
                    $csvData[self::DETAIL_FILE_INDEX_TRANS_SN] = $transSn; // 點數中心_特約商交易序號
                    $csvData[self::DETAIL_FILE_INDEX_QTY] = ''; // 數量
                    $csvData[self::DETAIL_FILE_INDEX_SUBTOTAL] = ''; // 商品小計
                    $csvData[self::DETAIL_FILE_INDEX_CUSTOM_SORT_VALUE] = $this->getCustomSortValueForDetailFileData($ecpayInvoiceUpdatedAt, $orderId, $invoiceStatus, $checkoutCounter); // 自定排序權重(排序完刪除)

                    $returnArray[] = $csvData;
                    break;

                case HotaiOrderItemInvoiceLogs::TYPE_DISCOUNT:
                    $includeTax = (int) abs(round((float) $itemLog->getIncludeTax()));
                    $tax = (int) abs(round((float) $itemLog->getTax()));
                    $excludeTax = (int) abs(round((float) $itemLog->getExcludeTax()));

                    $includeTax *= -1;
                    $tax *= -1;
                    $excludeTax *= -1;
                    $sellingPrice *= -1;
                    // $sellerBorneTotalAmount *= -1;
                    // $platformBorneTotalAmount *= -1;
                    $catalogPriceRuleDiscount *= -1;
                    $subtotal *= -1;

                    if ($this->reportHelper->needToReverseNumberForInvoiceStatus($invoiceStatus, $isReverse)) {
                        $includeTax *= -1;
                        $tax *= -1;
                        $excludeTax *= -1;
                        $sellingPrice *= -1;
                        // $sellerBorneTotalAmount *= -1;
                        // $platformBorneTotalAmount *= -1;
                        $catalogPriceRuleDiscount *= -1;
                        $subtotal *= -1;
                    }

                    $csvData = [];
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_CHANGE_DATE] = $this->reportHelper->getChangeDayTitle($ecpayInvoiceUpdatedAt); // 訂單結帳序號異動日
                    $csvData[self::DETAIL_FILE_INDEX_CHILD_ORDER_NUMBER] = $itemLog->getData("hotai_child_order_number"); // 子訂單編號
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS] = $this->reportHelper->getInvoiceStatusLabel($invoiceStatus); // 發票狀態
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_NUMBER] = $invoiceNumber; // 發票編號
                    $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_NUMBER] = $itemLog->getData("hotai_checkout_number"); // 訂單結帳序號
                    $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_COUNTER] = $checkoutCounter++; // 結帳項次
                    $csvData[self::DETAIL_FILE_INDEX_SKU] = ""; // 商品編號
                    $csvData[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] = self::PRODUCT_NAME_FOR_DISCOUNT; // 商品項目
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX] = $includeTax; // 發票_含稅
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_TAX] = $tax; // 發票_稅金
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] = $excludeTax; // 發票_未稅
                    $csvData[self::DETAIL_FILE_INDEX_LOGISTIC_STATUS] = ""; // 物流狀態
                    $csvData[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] = self::ACCOUNTING_GRADE_FOR_DISCOUNT; // HIFI拋帳會科設定
                    $csvData[self::DETAIL_FILE_INDEX_SELLER_NAME] = ""; // 特約商(應該是第二階段任務, 折扣怎麼找特約商)
                    $csvData[self::DETAIL_FILE_INDEX_SELLING_PRICE] = ""; // 廠商售價
                    $csvData[self::DETAIL_FILE_INDEX_DEALER_DISCOUNT] = ""; // 廠商負擔-購物車折扣
                    $csvData[self::DETAIL_FILE_INDEX_UNITY_NET_DISCOUNT] = ""; // 聯網負擔-購物車折扣
                    $csvData[self::DETAIL_FILE_INDEX_MARKETING_ACTIVITY] = ""; // 購物車折扣名稱
                    $csvData[self::DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_DISCOUNT] = $catalogPriceRuleDiscount; // 活動折扣
                    $csvData[self::DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_NAME] = $catalogPriceRuleName; // 活動折扣名稱
                    $csvData[self::DETAIL_FILE_INDEX_SALES_INTERFACE_ID] = $this->reportHelper->generateSalesInterfaceId($isReverse, $orderId, $invoiceCount); // 銷貨介面主檔ID
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE] = $invoiceProcessedCode; // 發票處理區分
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER] = $this->reportHelper->getInvoiceAllowancesNumber($invoiceStatus, $isReverse, $hotaiChildOrderNumber, $isCrossMonth); // 折讓單號
                    $csvData[self::DETAIL_FILE_INDEX_TRANS_SN] = ""; // 點數中心_特約商交易序號
                    $csvData[self::DETAIL_FILE_INDEX_QTY] = ''; // 數量
                    $csvData[self::DETAIL_FILE_INDEX_SUBTOTAL] = ''; // 商品小計
                    $csvData[self::DETAIL_FILE_INDEX_CUSTOM_SORT_VALUE] = $this->getCustomSortValueForDetailFileData($ecpayInvoiceUpdatedAt, $orderId, $invoiceStatus, $checkoutCounter); // 自定排序權重(排序完刪除)

                    $returnArray[] = $csvData;
                    break;

                case HotaiOrderItemInvoiceLogs::TYPE_SHIPPING:
                    $includeTax = (int) abs(round((float) $itemLog->getIncludeTax()));
                    $tax = (int) abs(round((float) $itemLog->getTax()));
                    $excludeTax = (int) abs(round((float) $itemLog->getExcludeTax()));

                    if ($this->reportHelper->needToReverseNumberForInvoiceStatus($invoiceStatus, $isReverse)) {
                        $includeTax *= -1;
                        $tax *= -1;
                        $excludeTax *= -1;
                        // $sellerBorneTotalAmount *= -1;
                        // $platformBorneTotalAmount *= -1;
                    }

                    $csvData = [];
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_CHANGE_DATE] = $this->reportHelper->getChangeDayTitle($ecpayInvoiceUpdatedAt); // 訂單結帳序號異動日
                    $csvData[self::DETAIL_FILE_INDEX_CHILD_ORDER_NUMBER] = $itemLog->getData("hotai_child_order_number"); // 子訂單編號
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS] = $this->reportHelper->getInvoiceStatusLabel($invoiceStatus); // 發票狀態
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_NUMBER] = $invoiceNumber; // 發票編號
                    $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_NUMBER] = $itemLog->getData("hotai_checkout_number"); // 訂單結帳序號
                    $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_COUNTER] = $checkoutCounter++; // 結帳項次
                    $csvData[self::DETAIL_FILE_INDEX_SKU] = ""; // 商品編號
                    $csvData[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] = self::PRODUCT_NAME_FOR_SHIPPING; // 商品項目
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX] = $includeTax; // 發票_含稅
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_TAX] = $tax; // 發票_稅金
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] = $excludeTax; // 發票_未稅
                    $csvData[self::DETAIL_FILE_INDEX_LOGISTIC_STATUS] = ""; // 物流狀態
                    $csvData[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] = self::ACCOUNTING_GRADE_FOR_PRODUCT; // HIFI拋帳會科設定
                    $csvData[self::DETAIL_FILE_INDEX_SELLER_NAME] = self::HOTAI_MAIN_DEALER_NAME; // 特約商
                    $csvData[self::DETAIL_FILE_INDEX_SELLING_PRICE] = ""; // 廠商售價
                    $csvData[self::DETAIL_FILE_INDEX_DEALER_DISCOUNT] = ""; // 廠商負擔-購物車折扣
                    $csvData[self::DETAIL_FILE_INDEX_UNITY_NET_DISCOUNT] = ""; // 聯網負擔-購物車折扣
                    $csvData[self::DETAIL_FILE_INDEX_MARKETING_ACTIVITY] = ""; // 購物車折扣名稱
                    $csvData[self::DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_DISCOUNT] = ""; // 活動折扣
                    $csvData[self::DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_NAME] = ""; // 活動折扣名稱
                    $csvData[self::DETAIL_FILE_INDEX_SALES_INTERFACE_ID] = $this->reportHelper->generateSalesInterfaceId($isReverse, $orderId, $invoiceCount); // 銷貨介面主檔ID
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE] = $invoiceProcessedCode; // 發票處理區分
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER] = $this->reportHelper->getInvoiceAllowancesNumber($invoiceStatus, $isReverse, $hotaiChildOrderNumber, $isCrossMonth); // 折讓單號
                    $csvData[self::DETAIL_FILE_INDEX_TRANS_SN] = ""; // 點數中心_特約商交易序號
                    $csvData[self::DETAIL_FILE_INDEX_QTY] = ''; // 數量
                    $csvData[self::DETAIL_FILE_INDEX_SUBTOTAL] = ''; // 商品小計
                    $csvData[self::DETAIL_FILE_INDEX_CUSTOM_SORT_VALUE] = $this->getCustomSortValueForDetailFileData($ecpayInvoiceUpdatedAt, $orderId, $invoiceStatus, $checkoutCounter); // 自定排序權重(排序完刪除)

                    $returnArray[] = $csvData;
                    break;

                default:
                    $csvData = [];
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_CHANGE_DATE] = "--"; // 訂單結帳序號異動日
                    $csvData[self::DETAIL_FILE_INDEX_CHILD_ORDER_NUMBER] = "--"; // 子訂單編號
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS] = "--"; // 發票狀態
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_NUMBER] = "--"; // 發票編號
                    $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_NUMBER] = "--"; // 訂單結帳序號
                    $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_COUNTER] = "--"; // 結帳項次
                    $csvData[self::DETAIL_FILE_INDEX_SKU] = "--"; // 商品編號
                    $csvData[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] = "--"; // 商品項目
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX] = 0; // 發票_含稅
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_TAX] = 0; // 發票_稅金
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] = 0; // 發票_未稅
                    $csvData[self::DETAIL_FILE_INDEX_LOGISTIC_STATUS] = "--"; // 物流狀態
                    $csvData[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] = "--"; // HIFI拋帳會科設定
                    $csvData[self::DETAIL_FILE_INDEX_SELLER_NAME] = "--"; // 特約商
                    $csvData[self::DETAIL_FILE_INDEX_SELLING_PRICE] = "--"; // 廠商售價
                    $csvData[self::DETAIL_FILE_INDEX_DEALER_DISCOUNT] = "--"; // 廠商負擔-購物車折扣
                    $csvData[self::DETAIL_FILE_INDEX_UNITY_NET_DISCOUNT] = "--"; // 聯網負擔-購物車折扣
                    $csvData[self::DETAIL_FILE_INDEX_MARKETING_ACTIVITY] = "--"; // 購物車折扣名稱
                    $csvData[self::DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_DISCOUNT] = "--"; // 活動折扣
                    $csvData[self::DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_NAME] = "--"; // 活動折扣名稱
                    $csvData[self::DETAIL_FILE_INDEX_SALES_INTERFACE_ID] = "--"; // 銷貨介面主檔ID
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE] = "--"; // 發票處理區分
                    $csvData[self::DETAIL_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER] = "--"; // 折讓單號
                    $csvData[self::DETAIL_FILE_INDEX_TRANS_SN] = "--"; // 點數中心_特約商交易序號
                    $csvData[self::DETAIL_FILE_INDEX_QTY] = "--"; // 數量
                    $csvData[self::DETAIL_FILE_INDEX_SUBTOTAL] = "--"; // 商品小計
                    $csvData[self::DETAIL_FILE_INDEX_CUSTOM_SORT_VALUE] = "--"; // 自定排序權重(排序完刪除)

                    $returnArray[] = $csvData;
                    break;
            }

            if (!isset($this->handleDiffDetailArray[$handleDiffDetailKey])) {
                $this->handleDiffDetailArray[$handleDiffDetailKey] = [];
            }
            $this->handleDiffDetailArray[$handleDiffDetailKey][] = $csvData;

            $preOrderLogId = $orderLogId;
        }

        return $returnArray;
    }

    /**
     * 根據order獲取退款狀態
     * @param Order $order
     * @return string
     */
    protected function getRefundStateLabelByOrder(int $hasRefund, int $orderId): string
    {
        if (empty($hasRefund)) {
            return "";
        }

        $parentOrderId = $this->reportHelper->getParentOrderIdByOrderId((int) $orderId);

        if (isset($this->refundStateCache[$parentOrderId])) {
            return $this->refundStateCache[$parentOrderId];
        }

        try {
            $this->refundStateCache[$parentOrderId] = $this->reportHelper->getRefundLabelByOrderId((int) $orderId);
        } catch (\Exception $e) {
            $this->refundStateCache[$parentOrderId] = $e->getMessage();
        }

        return $this->refundStateCache[$parentOrderId];
    }

    /**
     * 獲取建立sub record的目標日期
     * @return array
     */
    protected function getDateArrayForSubRecords(): array
    {
        $dateArray = [];

        $startDate = $this->getStartTimeDateObj(false)->format("Y-m-d");
        $endDate   = $this->getEndTimeDateObj(false)->format("Y-m-d");

        $startDateObj = new \DateTime($startDate, new \DateTimeZone(CommonHelper::TIMEZONE));
        $endDateObj   = new \DateTime($endDate, new \DateTimeZone(CommonHelper::TIMEZONE));
        $endDateObj   = $endDateObj->modify('+1 day');

        $period = new \DatePeriod(
            $startDateObj,
            new \DateInterval('P1D'),
            $endDateObj
        );

        foreach ($period as $dateObj) {
            $dateArray[] = $dateObj->format('Y-m-d');
        }

        return $dateArray;
    }

    protected function addHeaderAndSummaryToMainFileData(): void
    {
        $totalInclTax = 0;
        $totalTax     = 0;
        $totalExclTax = 0;

        foreach ($this->mainFileData as $rowData) {
            $totalInclTax += (int) $rowData[self::MAIN_FILE_INDEX_INVOICE_INCL_TAX];
            $totalTax += (int) $rowData[self::MAIN_FILE_INDEX_INVOICE_TAX];
            $totalExclTax += (int) $rowData[self::MAIN_FILE_INDEX_INVOICE_EXCL_TAX];
        }

        // add summary
        $totalData                                                  = [];
        $totalData[self::MAIN_FILE_INDEX_INVOICE_CHANGE_DATE]       = "合計"; // 訂單結帳序號異動日
        $totalData[self::MAIN_FILE_INDEX_CHILD_ORDER_NUMBER]        = ""; // 訂單編號
        $totalData[self::MAIN_FILE_INDEX_ORDER_STATUS_TITLE]        = ""; // 訂單狀態
        $totalData[self::MAIN_FILE_INDEX_PAYMENT_TITLE]             = ""; // 付款方式
        $totalData[self::MAIN_FILE_INDEX_INVOICE_STATUS]            = ""; // 發票狀態
        $totalData[self::MAIN_FILE_INDEX_INVOICE_NUMBER]            = ""; // 開立發票編號
        $totalData[self::MAIN_FILE_INDEX_CHECKOUT_NUMBER]           = ""; // 訂單結帳序號
        $totalData[self::MAIN_FILE_INDEX_CUSTOMER_IDENTIFIER]       = ""; // 買受人統編
        $totalData[self::MAIN_FILE_INDEX_CUSTOMER_NAME]             = ""; // 買受人名稱
        $totalData[self::MAIN_FILE_INDEX_INVOICE_INCL_TAX]          = $totalInclTax; // 發票_含稅
        $totalData[self::MAIN_FILE_INDEX_INVOICE_TAX]               = $totalTax; // 發票_稅金
        $totalData[self::MAIN_FILE_INDEX_INVOICE_EXCL_TAX]          = $totalExclTax; // 發票_未稅
        $totalData[self::MAIN_FILE_INDEX_REFUND_STATE]              = ""; // 退款狀態
        $totalData[self::MAIN_FILE_INDEX_INVOICE_DATE]              = ""; // 發票日期
        $totalData[self::MAIN_FILE_INDEX_ALLOWANCES_DATE]           = ""; // 折讓單日期
        $totalData[self::MAIN_FILE_INDEX_SALES_INTERFACE_ID]        = ""; // 銷貨介面主檔ID
        $totalData[self::MAIN_FILE_INDEX_INVOICE_STATUS_HIFI_CODE]  = ""; // 發票處理區分
        $totalData[self::MAIN_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER] = ""; // 折讓退回流水號
        $totalData[self::MAIN_FILE_INDEX_REVENUE_TYPE]              = ""; // 收入類別
        $this->mainFileData[]                                       = $totalData;

        // add header
        array_unshift($this->mainFileData, self::MAIN_FILE_CONTENT_HEADER);
    }

    protected function addHeaderAndSummaryToDetailFileData(): void
    {
        $totalInclTax = 0;
        $totalTax     = 0;
        $totalExclTax = 0;

        foreach ($this->detailFileData as $rowData) {
            $totalInclTax += (int) $rowData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX];
            $totalTax += (int) $rowData[self::DETAIL_FILE_INDEX_INVOICE_TAX];
            $totalExclTax += (int) $rowData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX];
        }

        // add summary
        $csvData                                                      = [];
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_CHANGE_DATE]         = "合計"; // 訂單結帳序號異動日
        $csvData[self::DETAIL_FILE_INDEX_CHILD_ORDER_NUMBER]          = $this->moneyDiffMessage ?? ""; // 子訂單編號
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS]              = ""; // 發票狀態
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_NUMBER]              = ""; // 發票編號
        $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_NUMBER]             = ""; // 訂單結帳序號
        $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_COUNTER]            = ""; // 結帳項次
        $csvData[self::DETAIL_FILE_INDEX_SKU]                         = ""; // 商品編號
        $csvData[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL]              = ""; // 商品項目
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX]            = $totalInclTax; // 發票_含稅
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_TAX]                 = $totalTax; // 發票_稅金
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX]            = $totalExclTax; // 發票_未稅
        $csvData[self::DETAIL_FILE_INDEX_LOGISTIC_STATUS]             = ""; // 物流狀態
        $csvData[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE]            = ""; // HIFI拋帳會科設定
        $csvData[self::DETAIL_FILE_INDEX_SELLER_NAME]                 = ""; // 特約商
        $csvData[self::DETAIL_FILE_INDEX_SELLING_PRICE]               = ""; // 廠商售價
        $csvData[self::DETAIL_FILE_INDEX_DEALER_DISCOUNT]             = ""; // 廠商負擔-購物車折扣
        $csvData[self::DETAIL_FILE_INDEX_UNITY_NET_DISCOUNT]          = ""; // 聯網負擔-購物車折扣
        $csvData[self::DETAIL_FILE_INDEX_MARKETING_ACTIVITY]          = ""; // 購物車折扣名稱
        $csvData[self::DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_DISCOUNT] = ""; // 活動折扣
        $csvData[self::DETAIL_FILE_INDEX_CATALOG_PRICE_RULE_NAME]     = ""; // 活動折扣名稱
        $csvData[self::DETAIL_FILE_INDEX_SALES_INTERFACE_ID]          = ""; // 銷貨介面主檔ID
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE]    = ""; // 發票處理區分
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER]   = ""; // 折讓單號
        $csvData[self::DETAIL_FILE_INDEX_TRANS_SN]                    = ""; // 點數中心_特約商交易序號
        $csvData[self::DETAIL_FILE_INDEX_QTY]                         = ""; // 數量
        $csvData[self::DETAIL_FILE_INDEX_SUBTOTAL]                    = ""; // 商品小計
        $this->detailFileData[]                                       = $csvData;

        // add header
        array_unshift($this->detailFileData, self::DETAIL_FILE_CONTENT_HEADER);
    }

    /**
     * 創建結帳訂單主檔並回傳檔名
     * (主檔代表檔案內每條紀錄為Magento order)
     * @return string
     */
    protected function createOriginalMainFile(): string
    {
        $handleDiff       = $this->_request->getParam('handle_diff');
        $handleAffix      = (!empty($handleDiff) && $handleDiff == 1) ? "_with_handle" : "_without_handle";
        $fileName         = date("Ymd_His") . "_original_main{$handleAffix}.csv";
        $filepath         = CommonHelper::FILE_FOLDER_PATH_ORIGINAL_MAIN_FILE . "/{$fileName}";
        $filepathWithRoot = $this->directoryList->getRoot() . "/" . $filepath;

        $this->directory->create(null);
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();

        $output = fopen($filepathWithRoot, 'w');

        foreach ($this->mainFileData as $rowData) {
            fputcsv($output, $rowData);
        }

        fclose($output);

        return $fileName;
    }

    /**
     * 創建上傳修改用訂單主檔
     * @return string
     */
    protected function createModifiedMainFile(): string
    {
        $modifiedFileName = str_replace('original', 'modified', $this->originalMainFileName);

        $this->directory->copyFile(
            CommonHelper::FILE_FOLDER_PATH_ORIGINAL_MAIN_FILE . "/{$this->originalMainFileName}",
            CommonHelper::FILE_FOLDER_PATH_MODIFIED_MAIN_FILE . "/{$modifiedFileName}"
        );

        return $modifiedFileName;
    }

    /**
     * 創建結帳訂單明細檔並回傳檔名
     * (明細檔代表檔案內每條紀錄為Magento order item)
     * @return string
     */
    protected function createOriginalDetailFile(): string
    {
        $handleDiff       = $this->_request->getParam('handle_diff');
        $handleAffix      = (!empty($handleDiff) && $handleDiff == 1) ? "_with_handle" : "_without_handle";
        $fileName         = date("Ymd_His") . "_original_detail{$handleAffix}.csv";
        $filepath         = CommonHelper::FILE_FOLDER_PATH_ORIGINAL_DETAIL_FILE . "/{$fileName}";
        $filepathWithRoot = $this->directoryList->getRoot() . "/" . $filepath;

        $this->directory->create(null);
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();

        $output = fopen($filepathWithRoot, 'w');

        foreach ($this->detailFileData as $rowData) {
            fputcsv($output, $rowData);
        }

        fclose($output);

        return $fileName;
    }

    /**
     * 創建上傳修改用訂單明細檔
     * @return string
     */
    protected function createModifiedDetailFile(): string
    {
        $modifiedFileName = str_replace('original', 'modified', $this->originalDetailFileName);

        $this->directory->copyFile(
            CommonHelper::FILE_FOLDER_PATH_ORIGINAL_DETAIL_FILE . "/{$this->originalDetailFileName}",
            CommonHelper::FILE_FOLDER_PATH_MODIFIED_DETAIL_FILE . "/{$modifiedFileName}"
        );

        return $modifiedFileName;
    }

    /**
     * 創建結帳訂單報表紀錄
     * @param string $mainFileName
     * @param string $detailFileName
     * @return void
     */
    protected function createSalesReportRecord(): int
    {
        $params = $this->_request->getParams();
        /** @var HifiSalesReportRecord $hifiSalesReportRecord */
        $hifiSalesReportRecord = $this->hifiSalesReportRecordFactory->create();
        $hifiSalesReportRecord->setInvoiceChangeStartDate($this->getStartTimeDateObj(useUtc: false)->format("Y-m-d H:i:s"));
        $hifiSalesReportRecord->setInvoiceChangeEndDate($this->getEndTimeDateObj(false)->format("Y-m-d H:i:s"));
        $hifiSalesReportRecord->setCollectingMethod((int) $params[HifiSalesReportRecord::COLLECTING_METHOD]);

        // 結帳日期??
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $taiwanDateObj->setTimestamp(time());
        $hifiSalesReportRecord->setClosingDate($taiwanDateObj->format("Y-m-d H:i:s"));

        $hifiSalesReportRecord->setSalesOrderIds(implode(",", $this->allOrderIds));

        $hifiSalesReportRecord->setOriginalMainFileName($this->originalMainFileName);
        $hifiSalesReportRecord->setModifiedMainFileName($this->modifiedMainFileName);
        $hifiSalesReportRecord->setOriginalDetailFileName($this->originalDetailFileName);
        $hifiSalesReportRecord->setModifiedDetailFileName($this->modifiedDetailFileName);
        $hifiSalesReportRecord->setCreatorAdminId((int) $this->authSession->getUser()->getId());

        $this->hifiSalesReportRecordRepository->save($hifiSalesReportRecord);

        return $hifiSalesReportRecord->getId();
    }

    /**
     * 創建sub record
     * @param int $mainRecordId
     * @return void
     */
    protected function createSalesReportSubRecord(int $mainRecordId): void
    {
        $statusArray = [
            SubRecord::INVOICE_STATUS_ISSUE      => "",
            SubRecord::INVOICE_STATUS_CANCEL     => "CM",
            SubRecord::INVOICE_STATUS_ALLOWANCES => "CM2",
            SubRecord::INVOICE_STATUS_NO_INVOICE => "CM3",
        ];

        $dateArray = $this->getDateArrayForSubRecords();

        foreach ($dateArray as $date) {
            $taiwanDateObj = new \DateTime();
            $taiwanDateObj->setTimezone(new \DateTimeZone(CommonHelper::TIMEZONE));
            $taiwanDateObj->setTimestamp(strtotime($date . " " . CommonHelper::TIMEZONE));
            $taiwanDateObj->setTime(0, 0, 0);
            $dateCode = $taiwanDateObj->format("Ymd");
            foreach ($statusArray as $invoiceStatus => $batchCodeSuffix) {
                /** @var SubRecord $subRecord */
                $subRecord = $this->subRecordFactory->create();
                $subRecord->setParentId($mainRecordId);
                $subRecord->setBatchCode("EP{$dateCode}{$batchCodeSuffix}");
                $subRecord->setInvoiceStatus($invoiceStatus);

                $utcDateObj = new \DateTime();
                $utcDateObj->setTimezone(new \DateTimeZone(CommonHelper::TIMEZONE));
                $utcDateObj->setTimestamp(strtotime($date . " " . CommonHelper::TIMEZONE));
                $subRecord->setInvoiceChangeDate($utcDateObj->format("Y-m-d"));

                $this->transaction->addObject($subRecord);
            }
        }

        $this->transaction->save();
    }

    protected function getCustomSortValueForDetailFileData(string $invoiceUpdatedAt, int $orderId, int $invoiceStatus, int $count)
    {
        $padOrderId       = str_pad((string) $orderId, 9, '0', STR_PAD_LEFT);
        $padInvoiceStatus = str_pad((string) $invoiceStatus, 3, '0', STR_PAD_LEFT);
        $padCount         = str_pad((string) $count, 3, '0', STR_PAD_LEFT);

        //return "{$invoiceUpdatedAt}_{$padOrderId}_{$padInvoiceStatus}_{$padCount}";
        return "{$invoiceUpdatedAt}_{$padOrderId}_{$padInvoiceStatus}";
    }

    protected function sortMainFileData()
    {
        usort($this->mainFileData, function ($a, $b) {
            if ($a[self::MAIN_FILE_INDEX_INVOICE_CHANGE_DATE] === $b[self::MAIN_FILE_INDEX_INVOICE_CHANGE_DATE]) {
                return strcmp($a[self::MAIN_FILE_INDEX_SALES_INTERFACE_ID], $b[self::MAIN_FILE_INDEX_SALES_INTERFACE_ID]);
            }
            return $a[self::MAIN_FILE_INDEX_INVOICE_CHANGE_DATE] <=> $b[self::MAIN_FILE_INDEX_INVOICE_CHANGE_DATE];
        });

        foreach ($this->mainFileData as $key => $data) {
            unset($this->mainFileData[$key][self::MAIN_FILE_INDEX_CUSTOM_IS_REVERSE]);
        }
    }

    protected function sortDetailFileData()
    {
        array_multisort(
            array_column($this->detailFileData, self::DETAIL_FILE_INDEX_CUSTOM_SORT_VALUE),
            SORT_ASC,
            array_column($this->detailFileData, self::DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE),
            SORT_DESC,
            array_column($this->detailFileData, self::DETAIL_FILE_INDEX_CHECKOUT_COUNTER),
            SORT_ASC,
            $this->detailFileData
        );

        foreach ($this->detailFileData as $key => $data) {
            unset($this->detailFileData[$key][self::DETAIL_FILE_INDEX_CUSTOM_SORT_VALUE]);
        }
    }

    protected function returnCreatePage(): \Magento\Framework\Controller\Result\Redirect
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->getUrl('*/create/form'));
        return $resultRedirect;
    }

    /**
     * 回到表格頁面
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function returnToListingPage(): \Magento\Framework\Controller\Result\Redirect
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->getUrl('*/display/records'));
        return $resultRedirect;
    }

    /**
     * 調整明細檔資料尾差
     * @return void
     */
    protected function handleMoneyDiffIfRequested(): void
    {
        $handleDiff = $this->_request->getParam('handle_diff');
        if (empty($handleDiff) || $handleDiff == 0) {
            return;
        }

        $splitDateMainFileData   = [];
        $splitDateDetailFileData = [];
        $resultDetailFileData    = [];

        foreach ($this->mainFileData as $mainRowData) {
            $date = $mainRowData[self::MAIN_FILE_INDEX_INVOICE_CHANGE_DATE];

            $splitDateMainFileData[$date][] = $mainRowData;
        }

        foreach ($this->detailFileData as $detailRowData) {
            $date = $detailRowData[self::DETAIL_FILE_INDEX_INVOICE_CHANGE_DATE];

            $splitDateDetailFileData[$date][] = $detailRowData;
        }

        // $this->moneyDiffMessage = "!!";
        foreach ($splitDateMainFileData as $date => $splitDateMainArray) {
            $targetMainArray   = $splitDateMainFileData[$date];
            $targetDetailArray = $splitDateDetailFileData[$date];

            $mainFileTotalInclTax   = 0;
            $mainFileTotalTax       = 0;
            $mainFileTotalExclTax   = 0;
            $detailFileTotalInclTax = 0;
            $detailFileTotalTax     = 0;
            $detailFileTotalExclTax = 0;
            $adjustIndex            = null;

            foreach ($targetMainArray as $mainRowData) {
                $mainFileTotalInclTax += (int) $mainRowData[self::MAIN_FILE_INDEX_INVOICE_INCL_TAX];
                $mainFileTotalTax += (int) $mainRowData[self::MAIN_FILE_INDEX_INVOICE_TAX];
                $mainFileTotalExclTax += (int) $mainRowData[self::MAIN_FILE_INDEX_INVOICE_EXCL_TAX];
            }

            foreach ($targetDetailArray as $detailRowData) {
                $detailFileTotalInclTax += (int) $detailRowData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX];
                $detailFileTotalTax += (int) $detailRowData[self::DETAIL_FILE_INDEX_INVOICE_TAX];
                $detailFileTotalExclTax += (int) $detailRowData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX];
            }

            $diff = $detailFileTotalTax - $mainFileTotalTax;

            if ($diff == 0) {
                continue;
            }

            // 尾差為正數(代表明細檔的稅金比主檔大, 要在明細檔從尾巴找起, 找一項"點數扣抵"扣回, 沒得找就找商品)
            if ($diff > 0) {
                $startIndex = count($targetDetailArray) - 1;
                for ($i = $startIndex; $i >= 0; $i--) {
                    // 已經最後一項, 直接算在這項上
                    if ($i == 0) {
                        $targetDetailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_TAX] -= $diff;
                        $targetDetailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] += $diff;

                        $adjustIndex = $i;
                        break;
                    }

                    // 專門找"點數折抵"的項目去調整
                    if ($targetDetailArray[$i][self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] != self::ACCOUNTING_GRADE_FOR_POINT) {
                        continue;
                    }
                    if ($targetDetailArray[$i][self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] != self::PRODUCT_NAME_FOR_POINT) {
                        continue;
                    }

                    // 如果可以找到"點數折抵"項目就把稅金欄位減掉$diff, 未稅欄位加上$diff
                    $targetDetailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_TAX] -= $diff;
                    $targetDetailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] += $diff;

                    $adjustIndex = $i;
                    break;
                }
            }

            // 尾差為負數(代表明細檔的稅金比主檔小, 要在明細檔從尾巴找起, 找一項"商品"加上)
            if ($diff < 0) {
                $startIndex = count($targetDetailArray) - 1;
                for ($i = $startIndex; $i >= 0; $i--) {
                    // 已經最後一項, 直接算在這項上
                    if ($i == 0) {
                        $targetDetailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_TAX] -= $diff;
                        $targetDetailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] += $diff;

                        $adjustIndex = $i;
                        break;
                    }

                    // 如果可以找到"商品"就把稅金欄位減掉$diff, 未稅欄位加上$diff
                    // 是"銷貨收入, 點數商城"而且不是"運費"
                    // $row[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] == self::ACCOUNTING_GRADE_FOR_PRODUCT
                    // $row[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] != self::PRODUCT_NAME_FOR_SHIPPING
                    if ($targetDetailArray[$i][self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] != self::ACCOUNTING_GRADE_FOR_PRODUCT) {
                        continue;
                    }
                    if ($targetDetailArray[$i][self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] == self::PRODUCT_NAME_FOR_SHIPPING) {
                        continue;
                    }

                    $targetDetailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_TAX] -= $diff;
                    $targetDetailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] += $diff;

                    $adjustIndex = $i;
                    break;
                }
            }

            // $adjustIndex += 1;
            // $this->moneyDiffMessage .= "主檔合計: {$mainFileTotalInclTax}={$mainFileTotalTax}+{$mainFileTotalExclTax}";
            // $this->moneyDiffMessage .= ", ";
            // $this->moneyDiffMessage .= "原明細檔合計: {$detailFileTotalInclTax}={$detailFileTotalTax}+{$detailFileTotalExclTax}";
            // $this->moneyDiffMessage .= ", ";

            // 加入調整訊息
            // $invoiceStatus          = $targetDetailArray[$adjustIndex][self::DETAIL_FILE_INDEX_INVOICE_STATUS];
            // $checkoutNumber         = $targetDetailArray[$adjustIndex][self::DETAIL_FILE_INDEX_CHECKOUT_NUMBER];
            // $checkoutCounter        = $targetDetailArray[$adjustIndex][self::DETAIL_FILE_INDEX_CHECKOUT_COUNTER];
            // $this->moneyDiffMessage .= "調整日期:{$date}|調整誤差:{$diff}|調整目標發票狀態:{$invoiceStatus}|調整目標結帳序號:{$checkoutNumber}|調整目標結帳項次:{$checkoutCounter}!!";

            foreach ($targetDetailArray as $detailData) {
                $resultDetailFileData[] = $detailData;
            }
        }

        $this->detailFileData = $resultDetailFileData;
    }

    protected function handleMoneyDiff($mainArray, $detailArray): array
    {
        $mainFileTotalInclTax   = 0;
        $mainFileTotalTax       = 0;
        $mainFileTotalExclTax   = 0;
        $detailFileTotalInclTax = 0;
        $detailFileTotalTax     = 0;
        $detailFileTotalExclTax = 0;
        $resultArray            = [];

        foreach ($mainArray as $mainRowData) {
            $mainFileTotalInclTax += (int) $mainRowData[self::MAIN_FILE_INDEX_INVOICE_INCL_TAX];
            $mainFileTotalTax += (int) $mainRowData[self::MAIN_FILE_INDEX_INVOICE_TAX];
            $mainFileTotalExclTax += (int) $mainRowData[self::MAIN_FILE_INDEX_INVOICE_EXCL_TAX];
            $isReverse            = (int) $mainRowData[self::MAIN_FILE_INDEX_CUSTOM_IS_REVERSE];
        }

        foreach ($detailArray as $detailRowData) {
            $detailFileTotalInclTax += (int) $detailRowData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX];
            $detailFileTotalTax += (int) $detailRowData[self::DETAIL_FILE_INDEX_INVOICE_TAX];
            $detailFileTotalExclTax += (int) $detailRowData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX];
        }

        $diff = $detailFileTotalTax - $mainFileTotalTax;

        if ($diff == 0) {
            return $detailArray;
        }

        // 要在明細檔從尾巴找起, 找一項"點數扣抵"扣回, 沒得找就找商品
        // 尾差為正數(明細檔稅金比較小)且正流程
        // 尾差為負數且逆流程
        if (($diff > 0 && $isReverse == 0) || ($diff < 0 && $isReverse == 1)) {
            $startIndex = count($detailArray) - 1;
            for ($i = $startIndex; $i >= 0; $i--) {
                // 已經最後一項, 直接算在這項上
                if ($i == 0) {
                    $detailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_TAX] -= $diff;
                    $detailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] += $diff;

                    $adjustIndex = $i;
                    break;
                }

                // 專門找"點數折抵"的項目去調整
                if ($detailArray[$i][self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] != self::ACCOUNTING_GRADE_FOR_POINT) {
                    continue;
                }
                if ($detailArray[$i][self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] != self::PRODUCT_NAME_FOR_POINT) {
                    continue;
                }

                // 如果可以找到"點數折抵"項目就把稅金欄位減掉$diff, 未稅欄位加上$diff
                $detailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_TAX] -= $diff;
                $detailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] += $diff;

                $adjustIndex = $i;
                break;
            }
        }

        // 要在明細檔從尾巴找起, 找一項"商品"加上
        // 尾差為負數(明細檔稅金比較大)且正流程
        // 尾差為正數且逆流程
        if (($diff < 0 && $isReverse == 0) || ($diff > 0 && $isReverse == 1)) {
            $startIndex = count($detailArray) - 1;
            for ($i = $startIndex; $i >= 0; $i--) {
                // 已經最後一項, 直接算在這項上
                if ($i == 0) {
                    $detailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_TAX] -= $diff;
                    $detailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] += $diff;

                    $adjustIndex = $i;
                    break;
                }

                // 如果可以找到"商品"就把稅金欄位減掉$diff, 未稅欄位加上$diff
                // 是"銷貨收入, 點數商城"而且不是"運費"
                // $row[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] == self::ACCOUNTING_GRADE_FOR_PRODUCT
                // $row[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] != self::PRODUCT_NAME_FOR_SHIPPING
                if ($detailArray[$i][self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] != self::ACCOUNTING_GRADE_FOR_PRODUCT) {
                    continue;
                }
                if ($detailArray[$i][self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] == self::PRODUCT_NAME_FOR_SHIPPING) {
                    continue;
                }

                $detailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_TAX] -= $diff;
                $detailArray[$i][self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] += $diff;

                $adjustIndex = $i;
                break;
            }
        }

        return $detailArray;
    }

    protected function getStartTimeDateObj(bool $useUtc = true): \DateTime
    {
        $taiwanDateObj = new \DateTime();
        $timestamp     = strtotime($this->params[HifiSalesReportRecord::INVOICE_CHANGE_START_DATE] . " " . CommonHelper::TIMEZONE);
        $taiwanDateObj->setTimezone(new \DateTimeZone(CommonHelper::TIMEZONE));
        $taiwanDateObj->setTimestamp($timestamp);
        $taiwanDateObj->setTime(0, 0, 0);
        $taiwanTimestamp = $taiwanDateObj->getTimestamp();

        if (!$useUtc) {
            return $taiwanDateObj;
        }

        $utcDateObj = new \DateTime();
        $utcDateObj->setTimezone(new \DateTimeZone("UTC"));
        $utcDateObj->setTimestamp($taiwanTimestamp);

        return $utcDateObj;
    }

    protected function getEndTimeDateObj(bool $useUtc = true): \DateTime
    {
        $taiwanDateObj = new \DateTime();
        $timestamp     = strtotime($this->params[HifiSalesReportRecord::INVOICE_CHANGE_END_DATE] . " " . CommonHelper::TIMEZONE);
        $taiwanDateObj->setTimezone(new \DateTimeZone(CommonHelper::TIMEZONE));
        $taiwanDateObj->setTimestamp($timestamp);
        $taiwanDateObj->setTime(23, 59, 59);

        if (!$useUtc) {
            return $taiwanDateObj;
        }

        $taiwanTimestamp = $taiwanDateObj->getTimestamp();

        $utcDateObj = new \DateTime();
        $utcDateObj->setTimezone(new \DateTimeZone("UTC"));
        $utcDateObj->setTimestamp($taiwanTimestamp);

        return $utcDateObj;
    }

    protected function adjustDetailDataBetweenInclTaxAndTax(): void
    {
        // $handleDiffDetailKey = "{$orderLogId}-{$invoiceNumber}-{$orderId}-{$invoiceStatus}-{$isReverse}-{$isCrossMonth}";
        // $this->handleDiffDetailArray[$handleDiffDetailKey][] = $csvData;

        foreach ($this->handleDiffDetailArray as $logSetKey => $logSet) {
            foreach ($logSet as $detailRecordKey => $detailRecord) {
                $inclTax   = $detailRecord[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX];
                $tax       = $detailRecord[self::DETAIL_FILE_INDEX_INVOICE_TAX];
                $expectTax = round($inclTax / 1.05 * 0.05);
                $taxDiff   = $tax - $expectTax;

                if ($taxDiff == 0) {
                    continue;
                }

                $this->handleDiffDetailArray[$logSetKey][$detailRecordKey][self::DETAIL_FILE_INDEX_INVOICE_TAX] -= $taxDiff;
                $this->handleDiffDetailArray[$logSetKey][$detailRecordKey][self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] += $taxDiff;
            }
        }
    }
}
