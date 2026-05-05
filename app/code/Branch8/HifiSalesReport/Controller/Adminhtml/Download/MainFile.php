<?php

declare(strict_types=1);

namespace Branch8\HifiSalesReport\Controller\Adminhtml\Download;

use Magento\Backend\App\Action;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecordRepository;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;

class MainFile extends Action
{
    /** @var HifiSalesReportRecordRepository */
    protected $hifiSalesReportRecordRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    public function __construct(
        HifiSalesReportRecordRepository $hifiSalesReportRecordRepository,
        CommonHelper $commonHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->hifiSalesReportRecordRepository = $hifiSalesReportRecordRepository;
        $this->commonHelper                    = $commonHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        $file_type = $this->_request->getParam('file_type');

        $fileContent = $this->commonHelper->getFileContent(
            $file_type,
            $this->getFileName($file_type)
        );

        return $this->commonHelper->getCsvDownloadResponse($this, $fileContent, $this->getFileName($file_type));
    }

    protected function getFileName(string $file_type): string
    {
        $record = $this->hifiSalesReportRecordRepository->get((int) $this->_request->getParam('record_id'));

        switch ($file_type) {
            case CommonHelper::FILE_TYPE_ORIGINAL_MAIN_FILE:
                return $record->getOriginalMainFileName();

            case CommonHelper::FILE_TYPE_MODIFIED_MAIN_FILE:
                return $record->getModifiedMainFileName();

            default:
                throw new \Exception(__("Invalid file type given.")->render());
        }
    }
}
