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
use Branch8\HifiSalesReport\Helper\Report as ReportHelper;
use Branch8\HifiSalesReport\Controller\Adminhtml\Create\Submit;
use Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface;
use Ecpay\Invoice\Model\HotaiOrderInvoiceLogs;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs\Collection as OrderInvoiceLogCollection;
use Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs\CollectionFactory as OrderInvoiceLogCollectionFactory;

class CheckFile3 extends Action
{
    const FILE_FOLDER = "var/HifiSalesReport";

    const CHECK_FILE_INDEX_TITLE                               = 0;
    const CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_ISSUE      = 1;
    const CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_CANCEL     = 2;
    const CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_ALLOWANCES = 3;
    const CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_ISSUE      = 4;
    const CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_CANCEL     = 5;
    const CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_ALLOWANCES = 6;
    const CHECK_FILE_INDEX_DIFF_INVOICE_STATUS_ISSUE           = 7;
    const CHECK_FILE_INDEX_DIFF_INVOICE_STATUS_CANCEL          = 8;
    const CHECK_FILE_INDEX_DIFF_INVOICE_STATUS_ALLOWANCES      = 9;

    const CHECK_FILE_ROW_COLUMN_COUNT = 10;
    const CHECK_FILE_HEADER           = [
        "發票異動日",
        "廠商結帳訂單主檔_發票開立(E1)",
        "廠商結帳訂單主檔_發票作廢(E2)",
        "廠商結帳訂單主檔_發票折讓(E3)",
        "綠界(稅務憑證)_發票開立(F1)",
        "綠界(稅務憑證)_發票作廢(F2)",
        "綠界(稅務憑證)_發票折讓(F3)",
        "CHECK_Diff = E1-F1",
        "CHECK_Diff = E2-F2",
        "CHECK_Diff = E3-F3",
    ];

    /** @var HifiSalesReportRecordRepository */
    protected $hifiSalesReportRecordRepository;

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

    /** @var OrderInvoiceLogCollectionFactory */
    protected $orderInvoiceLogCollectionFactory;

    protected $dateArray;
    protected $orderIdsEachDate = [];
    protected $checkFileContent;

    public function __construct(
        HifiSalesReportRecordRepository $hifiSalesReportRecordRepository,
        CommonHelper $commonHelper,
        Filesystem $filesystem,
        FileFactory $fileFactory,
        ReportHelper $reportHelper,
        OrderInvoiceLogCollectionFactory $orderInvoiceLogCollectionFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->hifiSalesReportRecordRepository  = $hifiSalesReportRecordRepository;
        $this->commonHelper                     = $commonHelper;
        $this->filesystem                       = $filesystem;
        $this->directory                        = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->fileFactory                      = $fileFactory;
        $this->reportHelper                     = $reportHelper;
        $this->orderInvoiceLogCollectionFactory = $orderInvoiceLogCollectionFactory;

        $this->dateArray        = [];
        $this->checkFileContent = [];

        parent::__construct($context);
    }

    // 因為是針對綠界的發票紀錄
    // 所以對於主檔與log表搜尋資料都只取"發票開立", "發票作廢", "發票折讓"三種狀態
    // 將主檔的含稅欄位加總到時間區分的對應狀態位置
    // 將log表的含稅欄位也加總到時間區分的對應狀態位置
    public function execute()
    {
        $record_id = (int) $this->_request->getParam('record_id');
        $record    = $this->hifiSalesReportRecordRepository->get($record_id);

        $this->writeMainFileDataIntoCheckFileContent($record);

        $this->writeEcpayLogDataIntoCheckFileContent();

        $this->caculateCheckFileDiffColumn();

        ksort($this->checkFileContent);

        $csvData  = $this->getFinalCsvDataForDownload();
        $fileName = $record->getId() . "_" . time() . "_check_file_3.csv";

        return $this->commonHelper->getCsvDownloadResponse($this, $csvData, $fileName);
    }

    protected function writeMainFileDataIntoCheckFileContent(HifiSalesReportRecord $record): void
    {
        $mainFileContent = $this->commonHelper->getFileContent(
            $this->commonHelper::FILE_TYPE_MODIFIED_MAIN_FILE,
            $record->getModifiedMainFileName()
        );

        foreach ($mainFileContent as $rowContent) {
            $date               = $rowContent[Submit::MAIN_FILE_INDEX_INVOICE_CHANGE_DATE];
            $invoiceStatusLabel = $rowContent[Submit::MAIN_FILE_INDEX_INVOICE_STATUS];

            if (!strtotime($date)) {
                continue;
            }

            if ($this->checkIsNoInvoiceRecordByInvoiceStatusLabel($invoiceStatusLabel)) {
                continue;
            }

            if (!isset($this->checkFileContent[$date])) {
                $this->checkFileContent[$date] = $this->generateCheckFileRow($date);
            }

            if (!isset($this->orderIdsEachDate[$date])) {
                $this->orderIdsEachDate[$date] = [];
            }
            $salesInterfaceId                = $rowContent[Submit::MAIN_FILE_INDEX_SALES_INTERFACE_ID];
            $this->orderIdsEachDate[$date][] = $this->reportHelper->getOrderIdFromSalesInterfaceId($salesInterfaceId);

            $invoiceStatusIndex = $this->getInvoiceStatusIndexForMainFile($invoiceStatusLabel);

            $date                                               = $rowContent[Submit::MAIN_FILE_INDEX_INVOICE_CHANGE_DATE];
            $this->dateArray[$date]                             = $date;
            $this->checkFileContent[$date][$invoiceStatusIndex] += (float) $rowContent[Submit::MAIN_FILE_INDEX_INVOICE_INCL_TAX];
        }
    }

    protected function checkIsNoInvoiceRecordByInvoiceStatusLabel(string $invoiceStatusLabel): bool
    {
        return $invoiceStatusLabel == ReportHelper::INVOICE_STATUS_LABEL_NO_INVOICE;
    }

    protected function getOrderLogsByDate(string $date): OrderInvoiceLogCollection
    {
        $startTime = $this->getStartDateObj($date, false)->format("Y-m-d H:i:s");
        $endTime   = $this->getEndDateObj($date, false)->format("Y-m-d H:i:s");

        $collection = $this->orderInvoiceLogCollectionFactory->create();
        $collection->addFieldToFilter(
            'main_table.created_at',
            ['gteq' => $startTime]
        );
        $collection->addFieldToFilter(
            'main_table.created_at',
            ['lteq' => $endTime]
        );
        $collection->addFieldToFilter(
            'main_table.status',
            [
                'in' => [
                    ReportHelper::INVOICE_STATUS_ISSUE,
                    ReportHelper::INVOICE_STATUS_CANCEL,
                    ReportHelper::INVOICE_STATUS_ALLOWANCES
                ]
            ]
        );
        $collection->addFieldToFilter(
            'main_table.order_id',
            ['in' => $this->orderIdsEachDate[$date]]
        );

        // $collection->load();

        return $collection;
    }

    protected function writeEcpayLogDataIntoCheckFileContent(): void
    {
        foreach ($this->dateArray as $date) {
            $logCollection = $this->getOrderLogsByDate($date);

            /** @var HotaiOrderInvoiceLogs $log */
            foreach ($logCollection as $log) {
                $invoiceStatusIndex = $this->getInvoiceStatusIndexForEcpayLog((int) $log->getStatus());
                $invoiceWithTax     = abs((int) $log->getIncludeTax());

                if ($this->reportHelper->needToReverseNumberForInvoiceStatus($log->getStatus(), $log->getIsReverse())) {
                    $invoiceWithTax *= -1;
                }

                $this->checkFileContent[$date][$invoiceStatusIndex] += $invoiceWithTax;
            }
        }
    }

    protected function getInvoiceStatusIndexForMainFile(string $invoiceStatusLabel): int
    {
        switch ($invoiceStatusLabel) {
            case ReportHelper::INVOICE_STATUS_LABEL_ISSUE:
                return self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_ISSUE;

            case ReportHelper::INVOICE_STATUS_LABEL_CANCEL:
                return self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_CANCEL;

            case ReportHelper::INVOICE_STATUS_LABEL_ALLOWANCES:
                return self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_ALLOWANCES;

            default:
                throw new \Exception("Unexpected invoice status label: {$invoiceStatusLabel}");
        }
    }

    protected function getInvoiceStatusIndexForEcpayLog(int $invoiceStatus): int
    {
        switch ($invoiceStatus) {
            case 1:
                return self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_ISSUE;

            case 2:
                return self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_CANCEL;

            case 3:
                return self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_ALLOWANCES;

            case 4:
                return self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_ISSUE;

            default:
                throw new \Exception("Unexpected invoice status: {$invoiceStatus}");
        }
    }

    protected function caculateCheckFileDiffColumn()
    {
        foreach ($this->checkFileContent as $index => $rowContent) {
            $this->checkFileContent[$index][self::CHECK_FILE_INDEX_DIFF_INVOICE_STATUS_ISSUE]      = $rowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_ISSUE] - $rowContent[self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_ISSUE];
            $this->checkFileContent[$index][self::CHECK_FILE_INDEX_DIFF_INVOICE_STATUS_CANCEL]     = $rowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_CANCEL] - $rowContent[self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_CANCEL];
            $this->checkFileContent[$index][self::CHECK_FILE_INDEX_DIFF_INVOICE_STATUS_ALLOWANCES] = $rowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_ALLOWANCES] - $rowContent[self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_ALLOWANCES];
        }
    }

    protected function caculateCheckFileTotalRow(): array
    {
        $totalArray = $this->generateCheckFileRow("合計");

        foreach ($this->checkFileContent as $rowContent) {
            $totalArray[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_ISSUE] += $rowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_ISSUE];
            $totalArray[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_CANCEL] += $rowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_CANCEL];
            $totalArray[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_ALLOWANCES] += $rowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_ALLOWANCES];
            $totalArray[self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_ISSUE] += $rowContent[self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_ISSUE];
            $totalArray[self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_CANCEL] += $rowContent[self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_CANCEL];
            $totalArray[self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_ALLOWANCES] += $rowContent[self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_ALLOWANCES];
        }

        $totalArray[self::CHECK_FILE_INDEX_DIFF_INVOICE_STATUS_ISSUE]      = "-";
        $totalArray[self::CHECK_FILE_INDEX_DIFF_INVOICE_STATUS_CANCEL]     = "-";
        $totalArray[self::CHECK_FILE_INDEX_DIFF_INVOICE_STATUS_ALLOWANCES] = "-";

        return $totalArray;
    }

    protected function generateCheckFileRow(string $firstColumnTitle): array
    {
        $resultArray = array_fill(0, self::CHECK_FILE_ROW_COLUMN_COUNT, null);

        $resultArray[self::CHECK_FILE_INDEX_TITLE]                               = $firstColumnTitle;
        $resultArray[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_ISSUE]      = 0;
        $resultArray[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_CANCEL]     = 0;
        $resultArray[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_STATUS_ALLOWANCES] = 0;
        $resultArray[self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_ISSUE]      = 0;
        $resultArray[self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_CANCEL]     = 0;
        $resultArray[self::CHECK_FILE_INDEX_ECPAY_LOG_INVOICE_STATUS_ALLOWANCES] = 0;
        $resultArray[self::CHECK_FILE_INDEX_DIFF_INVOICE_STATUS_ISSUE]           = 0;
        $resultArray[self::CHECK_FILE_INDEX_DIFF_INVOICE_STATUS_CANCEL]          = 0;
        $resultArray[self::CHECK_FILE_INDEX_DIFF_INVOICE_STATUS_ALLOWANCES]      = 0;

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

    protected function getStartDateObj(string $taiwanDate, bool $useUtc = true): \DateTime
    {
        $taiwanDateObj = new \DateTime();
        $timestamp     = strtotime($taiwanDate . " " . CommonHelper::TIMEZONE);
        $taiwanDateObj->setTimezone(new \DateTimeZone(CommonHelper::TIMEZONE));
        $taiwanDateObj->setTimestamp($timestamp);
        $taiwanDateObj->setTime(0, 0, second: 0);

        if (!$useUtc) {
            return $taiwanDateObj;
        }

        $taiwanTimestamp = $taiwanDateObj->getTimestamp();

        $utcDateObj = new \DateTime();
        $utcDateObj->setTimezone(new \DateTimeZone("UTC"));
        $utcDateObj->setTimestamp($taiwanTimestamp);

        return $utcDateObj;
    }

    protected function getEndDateObj(string $taiwanDate, bool $useUtc = true): \DateTime
    {
        $taiwanDateObj = new \DateTime();
        $timestamp     = strtotime($taiwanDate . " " . CommonHelper::TIMEZONE);
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
}
