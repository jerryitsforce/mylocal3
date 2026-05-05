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
use Branch8\HifiSalesReport\Controller\Adminhtml\Create\Submit;

class CheckFile2 extends Action
{
    const FILE_FOLDER = "var/HifiSalesReport";

    const CHECK_FILE_INDEX_TITLE                           = 0;
    const CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITH_TAX      = 1;
    const CHECK_FILE_INDEX_MAIN_FILE_INVOICE_TAX           = 2;
    const CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITHOUT_TAX   = 3;
    const CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_WITH_TAX    = 4;
    const CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_TAX         = 5;
    const CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_WITHOUT_TAX = 6;
    const CHECK_FILE_INDEX_DIFF_INVOICE_WITH_TAX           = 7;
    const CHECK_FILE_INDEX_DIFF_INVOICE_TAX                = 8;
    const CHECK_FILE_INDEX_DIFF_INVOICE_WITHOUT_TAX        = 9;

    const CHECK_FILE_ROW_COLUMN_COUNT = 10;
    const CHECK_FILE_HEADER           = [
        "結帳序號異動日",
        "廠商結帳訂單主檔_發票含稅(C1)",
        "廠商結帳訂單主檔_發票稅金(C2)",
        "廠商結帳訂單主檔_發票未稅(C3)",
        "廠商結帳訂單明細檔_發票含稅(D1)",
        "廠商結帳訂單明細檔_發票稅金(D2)",
        "廠商結帳訂單明細檔_發票未稅(D3)",
        "CHECK_Diff = C1-D1",
        "CHECK_Diff = C2-D2",
        "CHECK_Diff = C3-D3",
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

    protected $checkFileContent;

    public function __construct(
        HifiSalesReportRecordRepository $hifiSalesReportRecordRepository,
        CommonHelper $commonHelper,
        Filesystem $filesystem,
        FileFactory $fileFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->hifiSalesReportRecordRepository = $hifiSalesReportRecordRepository;
        $this->commonHelper                    = $commonHelper;
        $this->filesystem                      = $filesystem;
        $this->directory                       = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->fileFactory                     = $fileFactory;

        $this->checkFileContent = [];

        parent::__construct($context);
    }

    public function execute()
    {
        $record_id = (int) $this->_request->getParam('record_id');
        $record    = $this->hifiSalesReportRecordRepository->get($record_id);

        $this->writeMainFileDataIntoCheckFileContent($record);

        $this->writeDetailFileDataIntoCheckFileContent($record);

        $this->caculateCheckFileDiffColumn();

        ksort($this->checkFileContent);

        $csvData  = $this->getFinalCsvDataForDownload();
        $fileName = $record->getId() . "_" . time() . "_check_file_2.csv";

        return $this->commonHelper->getCsvDownloadResponse($this, $csvData, $fileName);
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

            $this->checkFileContent[$date][self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITH_TAX] += (float) $rowContent[Submit::MAIN_FILE_INDEX_INVOICE_INCL_TAX];
            $this->checkFileContent[$date][self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_TAX] += (float) $rowContent[Submit::MAIN_FILE_INDEX_INVOICE_TAX];
            $this->checkFileContent[$date][self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITHOUT_TAX] += (float) $rowContent[Submit::MAIN_FILE_INDEX_INVOICE_EXCL_TAX];
        }
    }

    protected function writeDetailFileDataIntoCheckFileContent(HifiSalesReportRecord $record)
    {
        $detailFileContent = $this->commonHelper->getFileContent(
            $this->commonHelper::FILE_TYPE_MODIFIED_DETAIL_FILE,
            $record->getModifiedDetailFileName()
        );

        foreach ($detailFileContent as $rowContent) {
            $date = $rowContent[Submit::DETAIL_FILE_INDEX_INVOICE_CHANGE_DATE];

            if (!strtotime($date)) {
                continue;
            }

            if (!isset($this->checkFileContent[$date])) {
                $this->checkFileContent[$date] = $this->generateCheckFileRow($date);
            }

            $this->checkFileContent[$date][self::CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_WITH_TAX] += (float) $rowContent[Submit::DETAIL_FILE_INDEX_INVOICE_INCL_TAX];
            $this->checkFileContent[$date][self::CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_TAX] += (float) $rowContent[Submit::DETAIL_FILE_INDEX_INVOICE_TAX];
            $this->checkFileContent[$date][self::CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_WITHOUT_TAX] += (float) $rowContent[Submit::DETAIL_FILE_INDEX_INVOICE_EXCL_TAX];
        }
    }

    protected function caculateCheckFileDiffColumn()
    {
        foreach ($this->checkFileContent as $index => $rowContent) {
            $this->checkFileContent[$index][self::CHECK_FILE_INDEX_DIFF_INVOICE_WITH_TAX]    = $rowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITH_TAX] - $rowContent[self::CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_WITH_TAX];
            $this->checkFileContent[$index][self::CHECK_FILE_INDEX_DIFF_INVOICE_TAX]         = $rowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_TAX] - $rowContent[self::CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_TAX];
            $this->checkFileContent[$index][self::CHECK_FILE_INDEX_DIFF_INVOICE_WITHOUT_TAX] = $rowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITHOUT_TAX] - $rowContent[self::CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_WITHOUT_TAX];
        }
    }

    protected function caculateCheckFileTotalRow(): array
    {
        $totalArray = $this->generateCheckFileRow("合計");

        foreach ($this->checkFileContent as $rowContent) {
            $totalArray[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITH_TAX] += $rowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITH_TAX];
            $totalArray[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_TAX] += $rowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_TAX];
            $totalArray[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITHOUT_TAX] += $rowContent[self::CHECK_FILE_INDEX_MAIN_FILE_INVOICE_WITHOUT_TAX];
            $totalArray[self::CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_WITH_TAX] += $rowContent[self::CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_WITH_TAX];
            $totalArray[self::CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_TAX] += $rowContent[self::CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_TAX];
            $totalArray[self::CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_WITHOUT_TAX] += $rowContent[self::CHECK_FILE_INDEX_DETAIL_FILE_INVOICE_WITHOUT_TAX];
            $totalArray[self::CHECK_FILE_INDEX_DIFF_INVOICE_WITH_TAX] += $rowContent[self::CHECK_FILE_INDEX_DIFF_INVOICE_WITH_TAX];
            $totalArray[self::CHECK_FILE_INDEX_DIFF_INVOICE_TAX] += $rowContent[self::CHECK_FILE_INDEX_DIFF_INVOICE_TAX];
            $totalArray[self::CHECK_FILE_INDEX_DIFF_INVOICE_WITHOUT_TAX] += $rowContent[self::CHECK_FILE_INDEX_DIFF_INVOICE_WITHOUT_TAX];
        }

        return $totalArray;
    }

    protected function generateCheckFileRow(string $firstColumnTitle): array
    {
        $resultArray = array_fill(0, self::CHECK_FILE_ROW_COLUMN_COUNT, null);

        $resultArray[self::CHECK_FILE_INDEX_TITLE] = $firstColumnTitle;

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
