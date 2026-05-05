<?php

declare(strict_types=1);

namespace Branch8\HifiSalesReport\Controller\Adminhtml\Create;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as OrderItemCollection;
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
use Branch8\HifiSalesReport\Helper\ReportOld as ReportHelper;
use Ecpay\Invoice\Model\HotaiOrderInvoiceLogs;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs\CollectionFactory as OrderLogCollectionFactory;

class SubmitOld extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    const MAIN_FILE_CONTENT_HEADER                  = [
        "發票異動日期",
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
        "買受人(發票抬頭)",
        "銷貨介面主檔ID",
        "發票處理區分",
        "折讓退回流水號"
    ];
    const MAIN_FILE_INDEX_INVOICE_CHANGE_DATE       = 0; // 發票異動日期
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
    const MAIN_FILE_INDEX_CUSTOMER_COMPANY          = 15; // 買受人(發票抬頭)
    const MAIN_FILE_INDEX_SALES_INTERFACE_ID        = 16; // 銷貨介面主檔ID
    const MAIN_FILE_INDEX_INVOICE_STATUS_HIFI_CODE  = 17; // 發票處理區分
    const MAIN_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER = 18; // 折讓退回流水號

    const DETAIL_FILE_CONTENT_HEADER                  = [
        "發票異動日期",
        "子訂單編號",
        "發票狀態",
        "發票編號",
        "訂單結帳序號",
        "結帳項次",
        "商品編號",
        "商品項目-(活動名稱)-(點數折抵)",
        "發票_含稅(to消費者)",
        "發票_稅金(to消費者)",
        "發票_未稅(to消費者)",
        "物流狀態",
        "HIFI拋帳會科設定",
        "特約商",
        "售價",
        "廠商負擔折扣-(等月底跟廠商結帳時使用)",
        "聯網負擔折扣",
        "銷貨介面主檔ID",
        "發票處理區分",
        "折讓單號"
    ];
    const DETAIL_FILE_INDEX_INVOICE_CHANGE_DATE       = 0; // 發票異動日期
    const DETAIL_FILE_INDEX_CHILD_ORDER_NUMBER        = 1; // 子訂單編號
    const DETAIL_FILE_INDEX_INVOICE_STATUS            = 2; // 發票狀態
    const DETAIL_FILE_INDEX_INVOICE_NUMBER            = 3; // 發票編號
    const DETAIL_FILE_INDEX_CHECKOUT_NUMBER           = 4; // 訂單結帳序號
    const DETAIL_FILE_INDEX_CHECKOUT_COUNTER          = 5; // 結帳項次
    const DETAIL_FILE_INDEX_SKU                       = 6; // 商品編號
    const DETAIL_FILE_INDEX_PRODUCT_DETAIL            = 7; // 商品項目-(活動名稱)-(點數折抵)
    const DETAIL_FILE_INDEX_INVOICE_INCL_TAX          = 8; // 發票_含稅(to消費者)
    const DETAIL_FILE_INDEX_INVOICE_TAX               = 9; // 發票_稅金(to消費者)
    const DETAIL_FILE_INDEX_INVOICE_EXCL_TAX          = 10; // 發票_未稅(to消費者)
    const DETAIL_FILE_INDEX_LOGISTIC_STATUS           = 11; // 物流狀態
    const DETAIL_FILE_INDEX_ACCOUNTING_GRADE          = 12; // HIFI拋帳會科設定
    const DETAIL_FILE_INDEX_SELLER_NAME               = 13; // 特約商
    const DETAIL_FILE_INDEX_SELLING_PRICE             = 14; // 售價
    const DETAIL_FILE_INDEX_DEALER_DISCOUNT           = 15; // 廠商負擔折扣-(等月底跟廠商結帳時使用)
    const DETAIL_FILE_INDEX_UNITY_NET_DISCOUNT        = 16; // 聯網負擔折扣
    const DETAIL_FILE_INDEX_SALES_INTERFACE_ID        = 17; // 銷貨介面主檔ID
    const DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE  = 18; // 發票處理區分
    const DETAIL_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER = 19; // 折讓單號

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

    /** @var \Magento\Framework\Filesystem\Directory\WriteInterface */
    protected $directory;

    /** @var AuthSession */
    protected $authSession;

    /** @var MessageManager */
    protected $messageManager;

    /** @var ReportHelper */
    protected $reportHelper;

    /** @var OrderLogCollectionFactory */
    protected $orderLogCollectionFactory;

    protected $params;
    protected $queryStartTime;
    protected $queryEndTime;
    protected $collectingMethods;
    protected $orderCollection;
    protected $orderItemCollection;

    protected $originalMainFileName;
    protected $modifiedMainFileName;
    protected $originalDetailFileName;
    protected $modifiedDetailFileName;
    protected $refundStateCache;

    protected $mainFileData     = [];
    protected $detailFileData   = [];
    protected $moneyDiffMessage = "";

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
        AuthSession $authSession,
        MessageManager $messageManager,
        ReportHelper $reportHelper,
        OrderLogCollectionFactory $orderLogCollectionFactory,
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
        $this->directory                       = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->authSession                     = $authSession;
        $this->messageManager                  = $messageManager;
        $this->reportHelper                    = $reportHelper;
        $this->orderLogCollectionFactory       = $orderLogCollectionFactory;

        $this->refundStateCache = [];

        parent::__construct($context);
    }

    public function execute()
    {
        $this->initParameters();

        if (!$this->checkStartTimeAndEndTime()) {
            $this->messageManager->addError(__("Invoice change end date must be greater than invoice change start date."));
            return $this->returnToListingPage();
        }

        if (!$this->checkInvoiceDateDoesNotOverlapWithExistingRecords()) {
            $this->messageManager->addError(__("Invoice change date can't overlap with existing records."));
            return $this->returnToListingPage();
        }

        $invoiceLogDataForMainFileData = $this->getInvoiceLogDataForMainFileData();
        foreach ($invoiceLogDataForMainFileData->getItems() as $invoiceLog) {
            echo json_encode($invoiceLog->toArray());
            echo "<br><br>";
        }
        die();

        $this->orderCollection     = $this->getOrderCollection();
        $this->orderItemCollection = $this->getOrderItemCollection();

        $this->mainFileData   = $this->generateMainFileData();
        $this->detailFileData = $this->generateDetailFileData();

        // 暫時不啟用調整尾數功能
        // $this->handleMoneyDiff();

        $this->addHeaderAndSummaryToMainFileData();
        $this->addHeaderAndSummaryToDetailFileData();

        $this->originalMainFileName   = $this->createOriginalMainFile();
        $this->modifiedMainFileName   = $this->createModifiedMainFile();
        $this->originalDetailFileName = $this->createOriginalDetailFile();
        $this->modifiedDetailFileName = $this->createModifiedDetailFile();

        $mainRecordId = $this->createSalesReportRecord();
        $this->createSalesReportSubRecord($mainRecordId);

        return $this->returnToListingPage();
    }

    /**
     * 參數初始化
     * @return void
     */
    protected function initParameters(): void
    {
        $this->params         = $this->_request->getParams();
        $this->queryStartTime = date("Y-m-d 00:00:00", strtotime($this->params[HifiSalesReportRecord::INVOICE_CHANGE_START_DATE]));
        $this->queryEndTime   = date("Y-m-d 23:59:59", strtotime($this->params[HifiSalesReportRecord::INVOICE_CHANGE_END_DATE]));

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
        return $this->queryStartTime < $this->queryEndTime;
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

    protected function getInvoiceLogDataForMainFileData()
    {
        $collection = $this->orderLogCollectionFactory->create();
        $collection->join(
            ['sales_order_payment' => 'sales_order_payment'],
            'main_table.order_id = sales_order_payment.parent_id',
            ['method']
        );

        $collection
            ->addFieldToFilter(
                'created_at',
                ['gteq' => $this->queryStartTime]
            )
            ->addFieldToFilter(
                'created_at',
                ['lteq' => $this->queryEndTime]
            )->addFieldToFilter(
                'sales_order_payment.method',
                ['in' => $this->collectingMethods]
            );

        return $collection;
    }

    protected function getPurePointDataForMainFileData()
    {

    }

    /**
     * 撈取指定條件的order collection
     * @return OrderCollection
     */
    protected function getOrderCollection(): OrderCollection
    {
        $orderCollection = $this->orderCollectionFactory->create();

        $orderCollection->getSelect()
            ->join(
                ['sales_order_payment' => 'sales_order_payment'],
                'main_table.entity_id = sales_order_payment.parent_id',
                ['method']
            );

        $orderCollection
            ->addFieldToFilter(
                'ecpay_invoice_updated_at',
                ['gteq' => $this->queryStartTime]
            )
            ->addFieldToFilter(
                'ecpay_invoice_updated_at',
                ['lteq' => $this->queryEndTime]
            )->addFieldToFilter(
                'hotai_checkout_number',
                ['neq' => null]
            )->addFieldToFilter(
                'sales_order_payment.method',
                ['in' => $this->collectingMethods]
            );

        $orderCollection->addOrder("ecpay_invoice_updated_at", "ASC");

        return $orderCollection;
    }

    /**
     * 根據撈取的order collection撈取所屬的order item collection
     * @return OrderItemCollection
     */
    protected function getOrderItemCollection(): OrderItemCollection
    {
        $orderIds = implode(",", $this->orderCollection->getAllIds());

        $itemCollection = $this->orderItemCollectionFactory->create();
        $itemCollection->join(
            ['sales_order' => 'sales_order'],
            'main_table.order_id = sales_order.entity_id',
            ['ecpay_invoice_updated_at']
        );
        $itemCollection->addFieldToFilter("order_id", ["in" => $orderIds]);

        $itemCollection->addOrder("ecpay_invoice_updated_at", "ASC");

        return $itemCollection;
    }

    protected function generateMainFileData(): array
    {
        $returnArray = [];

        /** @var Order $order */
        foreach ($this->orderCollection->getItems() as $order) {
            // 用order_id和ecpay_invoice_updated_at去綠界log表查有風險
            // 怕ecpay_invoice_updated_at有微小差距查不到
            /** @var HotaiOrderInvoiceLogs $orderInvoiceLog */
            $orderInvoiceLog = $this->reportHelper->getNewestInvoiceLogByOrderId((int) $order->getId());

            $invoiceStatus     = $order->getData("ecpay_invoice_status");
            $pointUsedArray    = $this->reportHelper->getInvoicePointsByOrder($order);
            $invoiceWithTax    = empty($orderInvoiceLog) ? 0 : $orderInvoiceLog->getIncludeTax();
            $invoiceTax        = empty($orderInvoiceLog) ? 0 : $orderInvoiceLog->getTax();
            $invoiceWithoutTax = empty($orderInvoiceLog) ? 0 : $orderInvoiceLog->getExcludeTax();
            // $invoiceWithTax    = empty($orderInvoiceLog) ? 0 : $orderInvoiceLog->getIncludeTax() + $pointUsedArray["withTax"];
            // $invoiceTax        = empty($orderInvoiceLog) ? 0 : $orderInvoiceLog->getTax() + $pointUsedArray["tax"];
            // $invoiceWithoutTax = empty($orderInvoiceLog) ? 0 : $orderInvoiceLog->getExcludeTax() + $pointUsedArray["withoutTax"];

            if ($this->reportHelper->needToReverseNumberForInvoiceStatus($invoiceStatus)) {
                $invoiceWithTax *= -1;
                $invoiceTax *= -1;
                $invoiceWithoutTax *= -1;
            }

            $csvData                                                  = [];
            $csvData[self::MAIN_FILE_INDEX_INVOICE_CHANGE_DATE]       = $this->reportHelper->getChangeDayTitle($order); // 訂單結帳序號異動日
            $csvData[self::MAIN_FILE_INDEX_CHILD_ORDER_NUMBER]        = $order->getData("hotai_child_order_number"); // 訂單編號
            $csvData[self::MAIN_FILE_INDEX_ORDER_STATUS_TITLE]        = $this->reportHelper->getStatusTitleByInvoiceStatus((int) $invoiceStatus); // 訂單狀態
            $csvData[self::MAIN_FILE_INDEX_PAYMENT_TITLE]             = $this->reportHelper->getPaymentTitleByOrder($order); // 付款方式
            $csvData[self::MAIN_FILE_INDEX_INVOICE_STATUS]            = $this->reportHelper->getInvoiceStatusLabel($order, (int) $invoiceStatus); // 發票狀態
            $csvData[self::MAIN_FILE_INDEX_INVOICE_NUMBER]            = $order->getData("ecpay_invoice_number"); // 開立發票編號
            $csvData[self::MAIN_FILE_INDEX_CHECKOUT_NUMBER]           = $order->getData("hotai_checkout_number"); // 訂單結帳序號
            $csvData[self::MAIN_FILE_INDEX_CUSTOMER_IDENTIFIER]       = $order->getData("ecpay_invoice_customer_identifier"); // 買受人統編
            $csvData[self::MAIN_FILE_INDEX_CUSTOMER_NAME]             = $this->reportHelper->getCustomerNameByOrder($order); // 買受人名稱
            $csvData[self::MAIN_FILE_INDEX_INVOICE_INCL_TAX]          = $invoiceWithTax; // 發票_含稅
            $csvData[self::MAIN_FILE_INDEX_INVOICE_TAX]               = $invoiceTax; // 發票_稅金
            $csvData[self::MAIN_FILE_INDEX_INVOICE_EXCL_TAX]          = $invoiceWithoutTax; // 發票_未稅
            $csvData[self::MAIN_FILE_INDEX_REFUND_STATE]              = $this->getRefundStateLabelByOrder($order); // 退款狀態
            $csvData[self::MAIN_FILE_INDEX_INVOICE_DATE]              = $this->reportHelper->getInvoiceDateForReportByOrder($order); // 發票日期
            $csvData[self::MAIN_FILE_INDEX_ALLOWANCES_DATE]           = $this->reportHelper->getAllowancesDateForReportByOrder($order); // 折讓單日期
            $csvData[self::MAIN_FILE_INDEX_CUSTOMER_COMPANY]          = $order->getData("ecpay_invoice_customer_company"); // 買受人(發票抬頭)
            $csvData[self::MAIN_FILE_INDEX_SALES_INTERFACE_ID]        = $this->reportHelper->generateSalesInterfaceId($order); // 銷貨介面主檔ID
            $csvData[self::MAIN_FILE_INDEX_INVOICE_STATUS_HIFI_CODE]  = $this->reportHelper->getInvoiceStatusCodeForHifiSync((int) $invoiceStatus); // 發票處理區分
            $csvData[self::MAIN_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER] = $this->reportHelper->getInvoiceAllowancesNumber($order);
            $returnArray[]                                            = $csvData;
        }

        return $returnArray;
    }

    protected function generateDetailFileData(): array
    {
        $returnArray = [];

        $orderItemArray = [];
        foreach ($this->orderItemCollection->getItems() as $orderItem) {
            $orderItemArray[] = $orderItem;
        }

        $preOrderId      = 0;
        $checkoutCounter = 1;
        /** @var OrderItem $orderItem */
        foreach ($orderItemArray as $key => $orderItem) {
            /** @var Order $order */
            $order = $this->orderCollection->getItemById($orderItem->getOrderId());
            if ($preOrderId != $order->getId()) {
                $checkoutCounter = 1;
            }

            $invoiceStatus               = $order->getData("ecpay_invoice_status");
            $invoicesValues              = $this->reportHelper->getInvoiceValuesByOrderItemAndOrderInvoiceLog($orderItem);
            $invoiceWithTaxTotalValue    = $invoicesValues["invoiceWithTaxTotalValue"];
            $invoiceTaxTotalValue        = $invoicesValues["invoiceTaxTotalValue"];
            $invoiceWithoutTaxTotalValue = $invoicesValues["invoiceWithoutTaxTotalValue"];

            if ($this->reportHelper->needToReverseNumberForInvoiceStatus($invoiceStatus)) {
                $invoiceWithTaxTotalValue *= -1;
                $invoiceTaxTotalValue *= -1;
                $invoiceWithoutTaxTotalValue *= -1;
            }

            $csvData                                                    = [];
            $csvData[self::DETAIL_FILE_INDEX_INVOICE_CHANGE_DATE]       = $this->reportHelper->getChangeDayTitle($order); // 訂單結帳序號異動日
            $csvData[self::DETAIL_FILE_INDEX_CHILD_ORDER_NUMBER]        = $order->getData("hotai_child_order_number"); // 子訂單編號
            $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS]            = $this->reportHelper->getInvoiceStatusLabel($order, (int) $invoiceStatus); // 發票狀態
            $csvData[self::DETAIL_FILE_INDEX_INVOICE_NUMBER]            = $order->getData("ecpay_invoice_number"); // 發票編號
            $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_NUMBER]           = $order->getData("hotai_checkout_number"); // 訂單結帳序號
            $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_COUNTER]          = $checkoutCounter++; // 結帳項次
            $csvData[self::DETAIL_FILE_INDEX_SKU]                       = $orderItem->getSku(); // 商品編號
            $csvData[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL]            = $orderItem->getName(); // 商品項目
            $csvData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX]          = $invoiceWithTaxTotalValue; // 發票_含稅(to消費者)
            $csvData[self::DETAIL_FILE_INDEX_INVOICE_TAX]               = $invoiceTaxTotalValue; // 發票_稅金(to消費者)
            $csvData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX]          = $invoiceWithoutTaxTotalValue; // 發票_未稅(to消費者)
            $csvData[self::DETAIL_FILE_INDEX_LOGISTIC_STATUS]           = $this->reportHelper->getLogisticStatus(); // 物流狀態
            $csvData[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE]          = self::ACCOUNTING_GRADE_FOR_PRODUCT; // HIFI拋帳會科設定
            $csvData[self::DETAIL_FILE_INDEX_SELLER_NAME]               = $orderItem->getData("seller_company_name"); // 特約商
            $csvData[self::DETAIL_FILE_INDEX_SELLING_PRICE]             = $this->reportHelper->getSellingPriceByOrderItem($orderItem); // 售價
            $csvData[self::DETAIL_FILE_INDEX_DEALER_DISCOUNT]           = $this->reportHelper->getDiscountFromDealer(); // 廠商負擔折扣
            $csvData[self::DETAIL_FILE_INDEX_UNITY_NET_DISCOUNT]        = $this->reportHelper->getUnityNetDiscountByOrderItem($orderItem); // 聯網負擔折扣
            $csvData[self::DETAIL_FILE_INDEX_SALES_INTERFACE_ID]        = $this->reportHelper->generateSalesInterfaceId($order); // 銷貨介面主檔ID
            $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE]  = $this->reportHelper->getInvoiceStatusCodeForHifiSync((int) $invoiceStatus); // 發票處理區分
            $csvData[self::DETAIL_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER] = $this->reportHelper->getInvoiceAllowancesNumber($order); // 折讓單號

            $returnArray[] = $csvData;

            // 點數折抵要單獨算做一筆紀錄(折抵數值預設是負的)
            if (!empty($orderItem->getData("row_total_point_used")) && $orderItem->getData("row_total_point_used") != 0) {
                $invoicePoints          = $this->reportHelper->getInvoicePointsByOrderItemAndOrderInvoiceLog($orderItem);
                $invoiceWithTaxPoint    = $invoicePoints["invoiceWithTaxPointValue"] * -1;
                $invoiceTaxPoint        = $invoicePoints["invoiceTaxPointValue"] * -1;
                $invoiceWithoutTaxPoint = $invoicePoints["invoiceWithoutTaxPointValue"] * -1;

                if ($this->reportHelper->needToReverseNumberForInvoiceStatus($invoiceStatus)) {
                    $invoiceWithTaxPoint *= -1;
                    $invoiceTaxPoint *= -1;
                    $invoiceWithoutTaxPoint *= -1;
                }

                $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_COUNTER]          = $checkoutCounter++; // 結帳項次
                $csvData[self::DETAIL_FILE_INDEX_SKU]                       = ""; // 商品編號
                $csvData[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL]            = self::PRODUCT_NAME_FOR_POINT; // 商品項目
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX]          = $invoiceWithTaxPoint; // 發票_含稅(to消費者)
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_TAX]               = $invoiceTaxPoint; // 發票_稅金(to消費者)
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX]          = $invoiceWithoutTaxPoint; // 發票_未稅(to消費者)
                $csvData[self::DETAIL_FILE_INDEX_LOGISTIC_STATUS]           = ""; // 物流狀態
                $csvData[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE]          = self::ACCOUNTING_GRADE_FOR_POINT; // HIFI拋帳會科設定
                $csvData[self::DETAIL_FILE_INDEX_SELLER_NAME]               = self::HOTAI_MAIN_DEALER_NAME; // 特約商
                $csvData[self::DETAIL_FILE_INDEX_SELLING_PRICE]             = ""; // 售價
                $csvData[self::DETAIL_FILE_INDEX_DEALER_DISCOUNT]           = ""; // 廠商負擔折扣
                $csvData[self::DETAIL_FILE_INDEX_UNITY_NET_DISCOUNT]        = ""; // 聯網負擔折扣
                $csvData[self::DETAIL_FILE_INDEX_SALES_INTERFACE_ID]        = $this->reportHelper->generateSalesInterfaceId($order); // 銷貨介面主檔ID
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE]  = $this->reportHelper->getInvoiceStatusCodeForHifiSync((int) $invoiceStatus); // 發票處理區分
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER] = $this->reportHelper->getInvoiceAllowancesNumber($order); // 折讓單號

                $returnArray[] = $csvData;
            }

            // 活動折抵要單獨算做一筆紀錄(折抵數值預設是負的)
            if (!empty($orderItem->getDiscountAmount()) && $orderItem->getDiscountAmount() != 0) {
                $invoiceDiscounts          = $this->reportHelper->getInvoiceDiscountsByOrderItemAndOrderInvoiceLog($orderItem);
                $invoiceWithTaxDiscount    = $invoiceDiscounts["invoiceWithTaxDiscountValue"] * -1;
                $invoiceTaxDiscount        = $invoiceDiscounts["invoiceTaxDiscountValue"] * -1;
                $invoiceWithoutTaxDiscount = $invoiceDiscounts["invoiceWithoutTaxDiscountValue"] * -1;

                if ($this->reportHelper->needToReverseNumberForInvoiceStatus($invoiceStatus)) {
                    $invoiceWithTaxDiscount *= -1;
                    $invoiceTaxDiscount *= -1;
                    $invoiceWithoutTaxDiscount *= -1;
                }

                $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_COUNTER]          = $checkoutCounter++; // 結帳項次
                $csvData[self::DETAIL_FILE_INDEX_SKU]                       = ""; // 商品編號
                $csvData[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL]            = self::PRODUCT_NAME_FOR_DISCOUNT; // 商品項目
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX]          = $invoiceWithTaxDiscount; // 發票_含稅(to消費者)
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_TAX]               = $invoiceTaxDiscount; // 發票_稅金(to消費者)
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX]          = $invoiceWithoutTaxDiscount; // 發票_未稅(to消費者)
                $csvData[self::DETAIL_FILE_INDEX_LOGISTIC_STATUS]           = ""; // 物流狀態
                $csvData[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE]          = self::ACCOUNTING_GRADE_FOR_DISCOUNT; // HIFI拋帳會科設定
                $csvData[self::DETAIL_FILE_INDEX_SELLER_NAME]               = ""; // 特約商(應該是第二階段任務, 折扣怎麼找特約商)
                $csvData[self::DETAIL_FILE_INDEX_SELLING_PRICE]             = ""; // 售價
                $csvData[self::DETAIL_FILE_INDEX_DEALER_DISCOUNT]           = ""; // 廠商負擔折扣
                $csvData[self::DETAIL_FILE_INDEX_UNITY_NET_DISCOUNT]        = ""; // 聯網負擔折扣
                $csvData[self::DETAIL_FILE_INDEX_SALES_INTERFACE_ID]        = $this->reportHelper->generateSalesInterfaceId($order); // 銷貨介面主檔ID
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE]  = $this->reportHelper->getInvoiceStatusCodeForHifiSync((int) $invoiceStatus); // 發票處理區分
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER] = $this->reportHelper->getInvoiceAllowancesNumber($order); // 折讓單號

                $returnArray[] = $csvData;
            }

            // 如果是order的最後一筆orderItem須手動加入運費紀錄(如果有的話)
            if ($this->checkIsLastItemForOrder($orderItem, $key, $orderItemArray) && $order->getShippingInclTax() != 0) {
                $shippingArray      = $this->reportHelper->getInvoiceShippingByOrder($order);
                $shippingWithTax    = $shippingArray["withTax"];
                $shippingTax        = $shippingArray["tax"];
                $shippingWithoutTax = $shippingArray["withoutTax"];

                if ($this->reportHelper->needToReverseNumberForInvoiceStatus($invoiceStatus)) {
                    $shippingWithTax *= -1;
                    $shippingTax *= -1;
                    $shippingWithoutTax *= -1;
                }

                $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_COUNTER]          = $checkoutCounter++; // 結帳項次
                $csvData[self::DETAIL_FILE_INDEX_SKU]                       = ""; // 商品編號
                $csvData[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL]            = self::PRODUCT_NAME_FOR_SHIPPING; // 商品項目
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX]          = $shippingWithTax; // 發票_含稅(to消費者)
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_TAX]               = $shippingTax; // 發票_稅金(to消費者)
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX]          = $shippingWithoutTax; // 發票_未稅(to消費者)
                $csvData[self::DETAIL_FILE_INDEX_LOGISTIC_STATUS]           = ""; // 物流狀態
                $csvData[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE]          = self::ACCOUNTING_GRADE_FOR_PRODUCT; // HIFI拋帳會科設定
                $csvData[self::DETAIL_FILE_INDEX_SELLER_NAME]               = self::HOTAI_MAIN_DEALER_NAME; // 特約商
                $csvData[self::DETAIL_FILE_INDEX_SELLING_PRICE]             = ""; // 售價
                $csvData[self::DETAIL_FILE_INDEX_DEALER_DISCOUNT]           = ""; // 廠商負擔折扣
                $csvData[self::DETAIL_FILE_INDEX_UNITY_NET_DISCOUNT]        = ""; // 聯網負擔折扣
                $csvData[self::DETAIL_FILE_INDEX_SALES_INTERFACE_ID]        = $this->reportHelper->generateSalesInterfaceId($order); // 銷貨介面主檔ID
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE]  = $this->reportHelper->getInvoiceStatusCodeForHifiSync((int) $invoiceStatus); // 發票處理區分
                $csvData[self::DETAIL_FILE_INDEX_INVOICE_ALLOWANCES_NUMBER] = $this->reportHelper->getInvoiceAllowancesNumber($order); // 折讓單號

                $returnArray[] = $csvData;
            }

            $preOrderId = $order->getId();
        }

        return $returnArray;
    }

    /**
     * 調整明細檔資料尾差
     * @return void
     */
    protected function handleMoneyDiff(): void
    {
        $mainFileTotalInclTax   = 0;
        $mainFileTotalTax       = 0;
        $mainFileTotalExclTax   = 0;
        $detailFileTotalInclTax = 0;
        $detailFileTotalTax     = 0;
        $detailFileTotalExclTax = 0;
        $adjustIndex            = null;

        foreach ($this->mainFileData as $mainRowData) {
            $mainFileTotalInclTax += (int) $mainRowData[self::MAIN_FILE_INDEX_INVOICE_INCL_TAX];
            $mainFileTotalTax += (int) $mainRowData[self::MAIN_FILE_INDEX_INVOICE_TAX];
            $mainFileTotalExclTax += (int) $mainRowData[self::MAIN_FILE_INDEX_INVOICE_EXCL_TAX];
        }

        foreach ($this->detailFileData as $detailRowData) {
            $detailFileTotalInclTax += (int) $detailRowData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX];
            $detailFileTotalTax += (int) $detailRowData[self::DETAIL_FILE_INDEX_INVOICE_TAX];
            $detailFileTotalExclTax += (int) $detailRowData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX];
        }

        $diff = $detailFileTotalTax - $mainFileTotalTax;

        if ($diff == 0) {
            return;
        }

        // 尾差為正數(代表明細檔的稅金比主檔大, 要在明細檔從尾巴找起, 找一項"點數扣抵"扣回, 沒得找就找商品)
        if ($diff > 0) {
            $startIndex = count($this->detailFileData) - 1;
            for ($i = $startIndex; $i >= 0; $i--) {
                // 已經最後一項, 直接算在這項上
                if ($i == 0) {
                    $this->detailFileData[$i][self::DETAIL_FILE_INDEX_INVOICE_TAX] -= $diff;
                    $this->detailFileData[$i][self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] += $diff;

                    $adjustIndex = $i;
                    break;
                }

                // 如果可以找到"點數折抵"項目就把稅金欄位減掉$diff, 未稅欄位加上$diff
                if ($this->detailFileData[$i][self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] == self::PRODUCT_NAME_FOR_POINT) {
                    $this->detailFileData[$i][self::DETAIL_FILE_INDEX_INVOICE_TAX] -= $diff;
                    $this->detailFileData[$i][self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] += $diff;

                    $adjustIndex = $i;
                    break;
                }
            }
        }

        // 尾差為負數(代表明細檔的稅金比主檔小, 要在明細檔從尾巴找起, 找一項"商品"加上)
        if ($diff < 0) {
            $startIndex = count($this->detailFileData) - 1;
            for ($i = $startIndex; $i >= 0; $i--) {
                // 已經最後一項, 直接算在這項上
                if ($i == 0) {
                    $this->detailFileData[$i][self::DETAIL_FILE_INDEX_INVOICE_TAX] -= $diff;
                    $this->detailFileData[$i][self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] += $diff;

                    $adjustIndex = $i;
                    break;
                }

                // 如果可以找到"商品"就把稅金欄位減掉$diff, 未稅欄位加上$diff
                // 是"銷貨收入, 點數商城"而且不是"運費"
                // $row[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] == self::ACCOUNTING_GRADE_FOR_PRODUCT
                // $row[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] != self::PRODUCT_NAME_FOR_SHIPPING
                if ($this->detailFileData[$i][self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE] != self::ACCOUNTING_GRADE_FOR_PRODUCT) {
                    continue;
                }

                if ($this->detailFileData[$i][self::DETAIL_FILE_INDEX_PRODUCT_DETAIL] == self::PRODUCT_NAME_FOR_SHIPPING) {
                    continue;
                }

                $this->detailFileData[$i][self::DETAIL_FILE_INDEX_INVOICE_TAX] -= $diff;
                $this->detailFileData[$i][self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX] += $diff;

                $adjustIndex = $i;
                break;
            }
        }

        $adjustIndex += 1;
        $this->moneyDiffMessage .= "主檔合計: {$mainFileTotalInclTax}={$mainFileTotalTax}+{$mainFileTotalExclTax}";
        $this->moneyDiffMessage .= ", ";
        $this->moneyDiffMessage .= "原明細檔合計: {$detailFileTotalInclTax}={$detailFileTotalTax}+{$detailFileTotalExclTax}";
        $this->moneyDiffMessage .= ", ";
        $this->moneyDiffMessage .= "調整項次: {$adjustIndex}";
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
        $totalData                                                 = [];
        $totalData[self::MAIN_FILE_INDEX_INVOICE_CHANGE_DATE]      = "合計"; // 訂單結帳序號異動日
        $totalData[self::MAIN_FILE_INDEX_CHILD_ORDER_NUMBER]       = ""; // 訂單編號
        $totalData[self::MAIN_FILE_INDEX_ORDER_STATUS_TITLE]       = ""; // 訂單狀態
        $totalData[self::MAIN_FILE_INDEX_PAYMENT_TITLE]            = ""; // 付款方式
        $totalData[self::MAIN_FILE_INDEX_INVOICE_STATUS]           = ""; // 發票狀態
        $totalData[self::MAIN_FILE_INDEX_INVOICE_NUMBER]           = ""; // 開立發票編號
        $totalData[self::MAIN_FILE_INDEX_CHECKOUT_NUMBER]          = ""; // 訂單結帳序號
        $totalData[self::MAIN_FILE_INDEX_CUSTOMER_IDENTIFIER]      = ""; // 買受人統編
        $totalData[self::MAIN_FILE_INDEX_CUSTOMER_NAME]            = ""; // 買受人名稱
        $totalData[self::MAIN_FILE_INDEX_INVOICE_INCL_TAX]         = $totalInclTax; // 發票_含稅
        $totalData[self::MAIN_FILE_INDEX_INVOICE_TAX]              = $totalTax; // 發票_稅金
        $totalData[self::MAIN_FILE_INDEX_INVOICE_EXCL_TAX]         = $totalExclTax; // 發票_未稅
        $totalData[self::MAIN_FILE_INDEX_REFUND_STATE]             = ""; // 退款狀態
        $totalData[self::MAIN_FILE_INDEX_INVOICE_DATE]             = ""; // 發票日期
        $totalData[self::MAIN_FILE_INDEX_ALLOWANCES_DATE]          = ""; // 折讓單日期
        $totalData[self::MAIN_FILE_INDEX_CUSTOMER_COMPANY]         = ""; // 買受人(發票抬頭)
        $totalData[self::MAIN_FILE_INDEX_SALES_INTERFACE_ID]       = ""; // 銷貨介面主檔ID
        $totalData[self::MAIN_FILE_INDEX_INVOICE_STATUS_HIFI_CODE] = ""; // 發票處理區分
        $this->mainFileData[]                                      = $totalData;

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
        $csvData                                                   = [];
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_CHANGE_DATE]      = "合計"; // 訂單結帳序號異動日
        $csvData[self::DETAIL_FILE_INDEX_CHILD_ORDER_NUMBER]       = $this->moneyDiffMessage ?? ""; // 子訂單編號
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS]           = ""; // 發票狀態
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_NUMBER]           = ""; // 發票編號
        $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_NUMBER]          = ""; // 訂單結帳序號
        $csvData[self::DETAIL_FILE_INDEX_CHECKOUT_COUNTER]         = ""; // 結帳項次
        $csvData[self::DETAIL_FILE_INDEX_SKU]                      = ""; // 商品編號
        $csvData[self::DETAIL_FILE_INDEX_PRODUCT_DETAIL]           = ""; // 商品項目
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_INCL_TAX]         = $totalInclTax; // 發票_含稅(to消費者)
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_TAX]              = $totalTax; // 發票_稅金(to消費者)
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX]         = $totalExclTax; // 發票_未稅(to消費者)
        $csvData[self::DETAIL_FILE_INDEX_LOGISTIC_STATUS]          = ""; // 物流狀態
        $csvData[self::DETAIL_FILE_INDEX_ACCOUNTING_GRADE]         = ""; // HIFI拋帳會科設定
        $csvData[self::DETAIL_FILE_INDEX_SELLER_NAME]              = ""; // 特約商
        $csvData[self::DETAIL_FILE_INDEX_SELLING_PRICE]            = ""; // 售價
        $csvData[self::DETAIL_FILE_INDEX_DEALER_DISCOUNT]          = ""; // 廠商負擔折扣
        $csvData[self::DETAIL_FILE_INDEX_UNITY_NET_DISCOUNT]       = ""; // 聯網負擔折扣
        $csvData[self::DETAIL_FILE_INDEX_SALES_INTERFACE_ID]       = ""; // 銷貨介面主檔ID
        $csvData[self::DETAIL_FILE_INDEX_INVOICE_STATUS_HIFI_CODE] = ""; // 發票處理區分
        $this->detailFileData[]                                    = $csvData;

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
        $fileName = date("Ymd_His") . "_original_main.csv";
        $filepath = CommonHelper::FILE_FOLDER_PATH_ORIGINAL_MAIN_FILE . "/{$fileName}";

        $this->directory->create(null);
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();

        foreach ($this->mainFileData as $rowData) {
            $stream->writeCsv($rowData);
        }

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
        $fileName = date("Ymd_His") . "_original_detail.csv";
        $filepath = CommonHelper::FILE_FOLDER_PATH_ORIGINAL_DETAIL_FILE . "/{$fileName}";

        $this->directory->create(null);
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();

        foreach ($this->detailFileData as $rowData) {
            $stream->writeCsv($rowData);
        }

        return $fileName;
    }

    protected function checkIsLastItemForOrder(OrderItem $currentOrderItem, int $currentKey, array $orderItemArray): bool
    {
        $nextKey = $currentKey + 1;

        if (!isset($orderItemArray[$nextKey])) {
            return true;
        }

        /** @var OrderItem $orderItem */
        $nextOrderItem = $orderItemArray[$nextKey];

        /** @var OrderItem $orderItem */
        $currentOrderId = $currentOrderItem->getOrderId();
        $nextOrderId    = $nextOrderItem->getOrderId();

        return $currentOrderId != $nextOrderId;
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
        $hifiSalesReportRecord->setInvoiceChangeStartDate($this->queryStartTime);
        $hifiSalesReportRecord->setInvoiceChangeEndDate($this->queryEndTime);
        $hifiSalesReportRecord->setCollectingMethod((int) $params[HifiSalesReportRecord::COLLECTING_METHOD]);
        // 結帳日期??
        $hifiSalesReportRecord->setClosingDate(date("Y-m-d H:i:s"));

        $hifiSalesReportRecord->setSalesOrderIds(implode(",", $this->orderCollection->getAllIds()));

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
            $dateCode = date("Ymd", strtotime($date));
            foreach ($statusArray as $invoiceStatus => $batchCodeSuffix) {
                /** @var SubRecord $subRecord */
                $subRecord = $this->subRecordFactory->create();
                $subRecord->setParentId($mainRecordId);
                $subRecord->setBatchCode("EP{$dateCode}{$batchCodeSuffix}");
                $subRecord->setInvoiceStatus($invoiceStatus);
                $subRecord->setInvoiceChangeDate($date);

                $this->transaction->addObject($subRecord);
            }
        }

        $this->transaction->save();
    }

    /**
     * 獲取建立sub record的目標日期
     * @return array
     */
    protected function getDateArrayForSubRecords(): array
    {
        $dateArray = [];

        $startDate = date("Y-m-d", strtotime($this->queryStartTime));
        $endDate   = date("Y-m-d", strtotime($this->queryEndTime));

        $startDateObj = new \DateTime($startDate);
        $endDateObj   = new \DateTime($endDate);
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

    /**
     * 根據order獲取退款狀態
     * @param Order $order
     * @return string
     */
    protected function getRefundStateLabelByOrder(Order $order): string
    {
        if (empty($order->getData("has_refund"))) {
            return "沒有退款紀錄不需進行查詢";
        }

        $parentOrderId = $this->reportHelper->getParentOrderIdByOrderId((int) $order->getId());

        if (isset($this->refundStateCache[$parentOrderId])) {
            return $this->refundStateCache[$parentOrderId];
        }

        try {
            $this->refundStateCache[$parentOrderId] = $this->reportHelper->getRefundLabelByOrderId((int) $order->getId());
        } catch (\Exception $e) {
            $this->refundStateCache[$parentOrderId] = $e->getMessage();
        }

        return $this->refundStateCache[$parentOrderId];
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
}