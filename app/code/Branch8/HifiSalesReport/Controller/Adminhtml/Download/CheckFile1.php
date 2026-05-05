<?php

declare(strict_types=1);

namespace Branch8\HifiSalesReport\Controller\Adminhtml\Download;

use Magento\Backend\App\Action;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecordRepository;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecord;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Branch8\HifiSalesReport\Helper\Report as ReportHelper;
use Branch8\HifiSalesReport\Controller\Adminhtml\Create\Submit;
use Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface as OrderLogInterface;
use Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface as OrderItemLogInterface;
use Ecpay\Invoice\Model\HotaiOrderItemInvoiceLogs as OrderItemLogModel;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs\CollectionFactory as OrderLogCollectionFactory;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderItemInvoiceLogs\CollectionFactory as OrderItemLogCollectionFactory;

class CheckFile1 extends Action
{
    const FILE_FOLDER = "var/HifiSalesReport";

    const CHECK_FILE_INDEX_TITLE                        = 0;
    const CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITH_TAX   = 1;
    const CHECK_FILE_INDEX_ORDER_DATA_POINT_USED        = 2;
    const CHECK_FILE_INDEX_ORDER_DATA_INVOICE_WITH_TAX  = 3;
    const CHECK_FILE_INDEX_ORDER_DATA_SHIPPING_WITH_TAX = 4;
    const CHECK_FILE_INDEX_DIFF_1                       = 5;

    const CHECK_FILE_ROW_COLUMN_COUNT = 6;
    const CHECK_FILE_HEADER           = [
        "結帳序號異動日",
        "廠商結帳訂單主檔_發票含稅(A1)",
        "商城訂單_點數折抵",
        "商城訂單_純金總額(B1)",
        "商城訂單_運費(B2)",
        "CHECK_Diff = A1-(B1+B2)"
    ];

    /** @var HifiSalesReportRecordRepository */
    protected $hifiSalesReportRecordRepository;

    /** @var OrderCollectionFactory */
    protected $orderCollectionFactory;

    /** @var OrderLogCollectionFactory */
    protected $orderLogCollectionFactory;

    /** @var OrderItemLogCollectionFactory */
    protected $orderItemLogCollectionFactory;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var Filesystem */
    protected $filesystem;

    /** @var WriteInterface */
    protected $directory;

    /** @var FileFactory */
    protected $fileFactory;

    /** @var ReportHelper */
    protected $reportHelper;

    protected $checkFileContent;
    protected $orderLogArray;
    protected $orderShippingArray;

    public function __construct(
        HifiSalesReportRecordRepository $hifiSalesReportRecordRepository,
        OrderCollectionFactory $orderCollectionFactory,
        OrderLogCollectionFactory $orderLogCollectionFactory,
        OrderItemLogCollectionFactory $orderItemLogCollectionFactory,
        CommonHelper $commonHelper,
        Filesystem $filesystem,
        FileFactory $fileFactory,
        ReportHelper $reportHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->hifiSalesReportRecordRepository = $hifiSalesReportRecordRepository;
        $this->orderCollectionFactory          = $orderCollectionFactory;
        $this->orderLogCollectionFactory       = $orderLogCollectionFactory;
        $this->orderItemLogCollectionFactory   = $orderItemLogCollectionFactory;
        $this->commonHelper                    = $commonHelper;
        $this->filesystem                      = $filesystem;
        $this->directory                       = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->fileFactory                     = $fileFactory;
        $this->reportHelper                    = $reportHelper;

        $this->checkFileContent   = [];
        $this->orderLogArray      = [];
        $this->orderShippingArray = [];

        parent::__construct($context);
    }

    // 由於主檔的資料會包含一筆order的正逆流程
    // 所以從資料庫查詢時不能去找sales_order
    // 因為sales_order只會有一筆最後的紀錄
    // 只能去查order log表找出"點數折抵", "純金總額", "運費"資料
    // 其中"點數折抵"和"純金總額"可以在order log表找到
    // "運費"則需要從order item log表找, 一筆order只會有一筆"運費"紀錄
    public function execute()
    {
        $record_id = (int) $this->_request->getParam('record_id');
        $record    = $this->hifiSalesReportRecordRepository->get($record_id);

        $this->initOrderData($record);

        $this->writeMainFileDataIntoCheckFileContent($record);

        $this->caculateCheckFileDiffColumn();

        ksort($this->checkFileContent);

        $csvData  = $this->getFinalCsvDataForDownload();
        $fileName = $record->getId() . "_" . time() . "_check_file_1.csv";

        return $this->commonHelper->getCsvDownloadResponse($this, $csvData, $fileName);
    }

    protected function initOrderData(HifiSalesReportRecord $record): void
    {
        // 先從主檔找出目標orderId
        $mainFileContent = $this->commonHelper->getFileContent(
            $this->commonHelper::FILE_TYPE_MODIFIED_MAIN_FILE,
            $record->getModifiedMainFileName()
        );

        foreach ($mainFileContent as $rowContent) {
            $childOrderNumber                 = $rowContent[Submit::MAIN_FILE_INDEX_CHILD_ORDER_NUMBER];
            $salesInterfaceId                 = $rowContent[Submit::MAIN_FILE_INDEX_SALES_INTERFACE_ID];
            $orderId                          = $this->reportHelper->getOrderIdFromSalesInterfaceId($salesInterfaceId);
            $groupOrderIds[$childOrderNumber] = $orderId;
        }
        // ---------------

        // 以orderId和時間區間找出order log表的對應歷程記錄
        $orderLogCollection = $this->orderLogCollectionFactory->create();
        $orderLogCollection
            ->addFieldToFilter(
                'main_table.created_at',
                ['gteq' => $record->getInvoiceChangeStartDate()]
            )->addFieldToFilter(
                'main_table.created_at',
                ['lteq' => $record->getInvoiceChangeEndDate()]
            )
            ->addFieldToFilter(
                OrderLogInterface::ORDER_ID,
                ['in' => $groupOrderIds]
            );
        $orderLogCollection->load();

        $this->orderLogArray = $orderLogCollection->getItems();
        // ---------------

        // 以orderLogId找出每筆order log紀錄底下的order item log"運費"紀錄
        $orderItemLogCollection = $this->orderItemLogCollectionFactory->create();
        $orderItemLogCollection->join(
            ['ecpay_invoice_hotai_order_invoice_logs' => 'ecpay_invoice_hotai_order_invoice_logs'],
            'main_table.hotai_order_invoice_log_id = ecpay_invoice_hotai_order_invoice_logs.hotai_order_invoice_logs_id',
            [
                'order_id' => 'order_id'
            ]
        );
        $orderItemLogCollection
            ->addFieldToFilter(
                OrderItemLogInterface::HOTAI_ORDER_INVOICE_LOG_ID,
                ['in' => array_keys($this->orderLogArray)]
            )->addFieldToFilter(
                OrderItemLogInterface::TYPE,
                ['eq' => OrderItemLogModel::TYPE_SHIPPING]
            );
        $orderItemLogCollection->load();

        /** @var OrderItemLogInterface $orderItemLog */
        foreach ($orderItemLogCollection->getItems() as $orderItemLog) {
            $orderLogId = $orderItemLog->getHotaiOrderInvoiceLogId();

            $this->orderShippingArray[$orderLogId] = $orderItemLog->getIncludeTax();
        }
    }

    protected function writeMainFileDataIntoCheckFileContent(HifiSalesReportRecord $record)
    {
        $mainFileContent = $this->commonHelper->getFileContent(
            $this->commonHelper::FILE_TYPE_MODIFIED_MAIN_FILE,
            $record->getModifiedMainFileName()
        );

        foreach ($mainFileContent as $rowContent) {
            $date = $rowContent[Submit::MAIN_FILE_INDEX_INVOICE_CHANGE_DATE];

            if (!strtotime($date)) {
                continue;
            }

            if (!isset($this->checkFileContent[$date])) {
                $this->checkFileContent[$date] = $this->generateCheckFileRow($date);
            }

            $this->checkFileContent[$date][self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITH_TAX] += (int) $rowContent[Submit::MAIN_FILE_INDEX_INVOICE_INCL_TAX];
        }

        /** @var OrderLogInterface $orderLog */
        foreach ($this->orderLogArray as $orderLog) {
            $date = $this->reportHelper->getChangeDayTitle($orderLog->getCreatedAt());

            if (!isset($this->checkFileContent[$date])) {
                $this->checkFileContent[$date] = $this->generateCheckFileRow($date);
            }

            $orderLogId           = $orderLog->getHotaiOrderInvoiceLogsId();
            $invoiceStatus        = (int) $orderLog->getStatus();
            $isReverse            = (int) $orderLog->getIsReverse();
            $pointFromDb          = abs((int) $orderLog->getPointUsed());
            $shippingFromDb       = abs(isset($this->orderShippingArray[$orderLogId]) ? (int) $this->orderShippingArray[$orderLogId] : 0);
            $invoiceWithTaxFromDb = abs((int) $orderLog->getIncludeTax()) - $shippingFromDb;

            // 點數折抵預設以負項去看
            $pointFromDb *= -1;

            if ($this->reportHelper->needToReverseNumberForInvoiceStatus($invoiceStatus, $isReverse)) {
                $pointFromDb *= -1;
                $invoiceWithTaxFromDb *= -1;
                $shippingFromDb *= -1;
            }

            $this->checkFileContent[$date][self::CHECK_FILE_INDEX_ORDER_DATA_POINT_USED] += $pointFromDb;
            $this->checkFileContent[$date][self::CHECK_FILE_INDEX_ORDER_DATA_INVOICE_WITH_TAX] += $invoiceWithTaxFromDb;
            $this->checkFileContent[$date][self::CHECK_FILE_INDEX_ORDER_DATA_SHIPPING_WITH_TAX] += $shippingFromDb;
        }
    }

    protected function caculateCheckFileDiffColumn()
    {
        foreach ($this->checkFileContent as $index => $rowContent) {
            $this->checkFileContent[$index][self::CHECK_FILE_INDEX_DIFF_1] = $this->caculateDiff1($rowContent);
        }
    }

    protected function caculateDiff1(array $checkFileRowContent): float
    {
        $mainFileInvoiceWithTax = (float) $checkFileRowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITH_TAX];
        $orderDataTotalPrice    = (float) $checkFileRowContent[self::CHECK_FILE_INDEX_ORDER_DATA_INVOICE_WITH_TAX];
        $orderDataShipping      = (float) $checkFileRowContent[self::CHECK_FILE_INDEX_ORDER_DATA_SHIPPING_WITH_TAX];

        return $mainFileInvoiceWithTax - ($orderDataTotalPrice + $orderDataShipping);
    }

    protected function caculateCheckFileTotalRow(): array
    {
        $totalArray = $this->generateCheckFileRow("合計");

        $totalArray[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITH_TAX]   = 0;
        $totalArray[self::CHECK_FILE_INDEX_ORDER_DATA_POINT_USED]        = 0;
        $totalArray[self::CHECK_FILE_INDEX_ORDER_DATA_INVOICE_WITH_TAX]  = 0;
        $totalArray[self::CHECK_FILE_INDEX_ORDER_DATA_SHIPPING_WITH_TAX] = 0;
        $totalArray[self::CHECK_FILE_INDEX_DIFF_1]                       = 0;

        foreach ($this->checkFileContent as $rowContent) {
            $totalArray[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITH_TAX] += (float) $rowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITH_TAX];
            $totalArray[self::CHECK_FILE_INDEX_ORDER_DATA_POINT_USED] += (float) $rowContent[self::CHECK_FILE_INDEX_ORDER_DATA_POINT_USED];
            $totalArray[self::CHECK_FILE_INDEX_ORDER_DATA_INVOICE_WITH_TAX] += (float) $rowContent[self::CHECK_FILE_INDEX_ORDER_DATA_INVOICE_WITH_TAX];
            $totalArray[self::CHECK_FILE_INDEX_ORDER_DATA_SHIPPING_WITH_TAX] += (float) $rowContent[self::CHECK_FILE_INDEX_ORDER_DATA_SHIPPING_WITH_TAX];
            $totalArray[self::CHECK_FILE_INDEX_DIFF_1] += (float) $rowContent[self::CHECK_FILE_INDEX_DIFF_1];
        }

        return $totalArray;
    }

    protected function generateCheckFileRow(string $firstColumnTitle): array
    {
        $resultArray = array_fill(0, self::CHECK_FILE_ROW_COLUMN_COUNT, null);

        $resultArray[self::CHECK_FILE_INDEX_TITLE]                        = $firstColumnTitle;
        $resultArray[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITH_TAX]   = 0;
        $resultArray[self::CHECK_FILE_INDEX_ORDER_DATA_POINT_USED]        = 0;
        $resultArray[self::CHECK_FILE_INDEX_ORDER_DATA_INVOICE_WITH_TAX]  = 0;
        $resultArray[self::CHECK_FILE_INDEX_ORDER_DATA_SHIPPING_WITH_TAX] = 0;
        $resultArray[self::CHECK_FILE_INDEX_DIFF_1]                       = 0;

        return $resultArray;
    }

    protected function getFinalCsvDataForDownload(): array
    {
        $csvData = [];

        $csvData[] = self::CHECK_FILE_HEADER;

        foreach ($this->checkFileContent as $rowData) {
            $csvData[] = $rowData;
        }

        $csvData[] = $this->caculateCheckFileTotalRow();

        return $csvData;
    }
}
