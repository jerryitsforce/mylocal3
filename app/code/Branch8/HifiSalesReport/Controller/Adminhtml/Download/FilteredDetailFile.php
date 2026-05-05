<?php

declare(strict_types=1);

namespace Branch8\HifiSalesReport\Controller\Adminhtml\Download;

use Magento\Backend\App\Action;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecord;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecordRepository;
use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord;
use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecordRepository;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\App\Response\Http\FileFactory;

class FilteredDetailFile extends Action
{
    const FILE_FOLDER = "var/HifiSalesReport";

    /** @var HifiSalesReportRecordRepository */
    protected $mainRecordRepository;

    /** @var HifiSalesReportSubRecordRepository */
    protected $subRecordRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var Filesystem */
    protected $filesystem;

    /** @var WriteInterface */
    protected $directory;

    /** @var FileFactory */
    protected $fileFactory;

    protected $mainRecord;
    protected $subRecord;

    public function __construct(
        HifiSalesReportRecordRepository $mainRecordRepository,
        HifiSalesReportSubRecordRepository $subRecordRepository,
        CommonHelper $commonHelper,
        Filesystem $filesystem,
        FileFactory $fileFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->mainRecordRepository = $mainRecordRepository;
        $this->subRecordRepository  = $subRecordRepository;
        $this->commonHelper         = $commonHelper;
        $this->filesystem           = $filesystem;
        $this->directory            = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->fileFactory          = $fileFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $subRecordId = $this->getRequest()->getParam("record_id");

        $this->subRecord = $this->subRecordRepository->get((int) $subRecordId);

        $this->mainRecord = $this->mainRecordRepository->get((int) $this->subRecord->getParentId());

        $csvData            = $this->getFinalCsvDataForDownload();
        $invoiceStatusLabel = $this->commonHelper->getInvoiceStatusLabel((int) $this->subRecord->getInvoiceStatus());
        $fileName           = $this->subRecord->getId() . "_" . time() . "_明細檔_{$invoiceStatusLabel}.csv";

        return $this->commonHelper->getCsvDownloadResponse($this, $csvData, $fileName);
    }

    protected function getFinalCsvDataForDownload(): array
    {
        $detailFileContent = $this->commonHelper->getFileContent(
            CommonHelper::FILE_TYPE_MODIFIED_DETAIL_FILE,
            $this->mainRecord->getModifiedDetailFileName()
        );

        return $this->commonHelper->getFilteredDetailFileContent(
            $detailFileContent,
            $this->subRecord
        );
    }
}
