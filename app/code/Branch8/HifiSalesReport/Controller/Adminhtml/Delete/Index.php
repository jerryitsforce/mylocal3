<?php

declare(strict_types=1);

namespace Branch8\HifiSalesReport\Controller\Adminhtml\Delete;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Backend\App\Action;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecord;
use Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportRecord as RecordResource;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecordRepository;
use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecordRepository as SubRecordRepository;
use Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportSubRecord\Collection as SubRecordCollection;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\DB\Transaction;

class Index extends Action
{
    const LOG_NAME      = 'HifiSalesReport';
    const LOG_FILE_NAME = 'DeleteRecord';

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var AuthSession */
    protected $authSession;

    /** @var HifiSalesReportRecordRepository */
    protected $hifiSalesReportRecordRepository;

    /** @var SubRecordRepository */
    protected $subRecordRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var ResultFactory */
    protected $resultFactory;

    /** @var MessageManager */
    protected $messageManager;

    /** @var RecordResource */
    protected $recordResource;

    /** @var File */
    protected $file;

    /** @var Transaction */
    protected $transaction;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        AuthSession $authSession,
        HifiSalesReportRecordRepository $hifiSalesReportRecordRepository,
        SubRecordRepository $subRecordRepository,
        CommonHelper $commonHelper,
        ResultFactory $resultFactory,
        MessageManager $messageManager,
        RecordResource $recordResource,
        File $file,
        Transaction $transaction,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->hotaiCoreCommonHelper           = $hotaiCoreCommonHelper;
        $this->authSession                     = $authSession;
        $this->hifiSalesReportRecordRepository = $hifiSalesReportRecordRepository;
        $this->subRecordRepository             = $subRecordRepository;
        $this->commonHelper                    = $commonHelper;
        $this->resultFactory                   = $resultFactory;
        $this->messageManager                  = $messageManager;
        $this->recordResource                  = $recordResource;
        $this->file                            = $file;
        $this->transaction                     = $transaction;

        parent::__construct($context);
    }

    public function execute()
    {
        $recordId = (int) $this->getRequest()->getParam("record_id");
        $record   = $this->hifiSalesReportRecordRepository->get($recordId);

        // check record status for delete
        if (!$this->checkStatusForDelete($record)) {
            return $this->redirect("Delete is not allowed for current record status.");
        }

        // delete file
        $this->deleteFile($record);

        // delete main record and sub records
        $this->deleteRecord($record);

        return $this->redirect("Delete success.");
    }

    protected function checkStatusForDelete(HifiSalesReportRecord $record): bool
    {
        return $record->getStatus() == HifiSalesReportRecord::STATUS_CREATED;
    }

    protected function deleteFile(HifiSalesReportRecord $record): void
    {
        $filePath = $this->commonHelper->getFileAbsolutePath(CommonHelper::FILE_TYPE_ORIGINAL_MAIN_FILE, $record->getOriginalMainFileName());
        if ($this->file->isExists($filePath)) {
            $this->file->deleteFile($filePath);
        }

        $filePath = $this->commonHelper->getFileAbsolutePath(CommonHelper::FILE_TYPE_MODIFIED_MAIN_FILE, $record->getModifiedMainFileName());
        if ($this->file->isExists($filePath)) {
            $this->file->deleteFile($filePath);
        }

        $filePath = $this->commonHelper->getFileAbsolutePath(CommonHelper::FILE_TYPE_ORIGINAL_DETAIL_FILE, $record->getOriginalDetailFileName());
        if ($this->file->isExists($filePath)) {
            $this->file->deleteFile($filePath);
        }

        $filePath = $this->commonHelper->getFileAbsolutePath(CommonHelper::FILE_TYPE_MODIFIED_DETAIL_FILE, $record->getModifiedDetailFileName());
        if ($this->file->isExists($filePath)) {
            $this->file->deleteFile($filePath);
        }
    }

    protected function deleteRecord(HifiSalesReportRecord $record): void
    {
        $subRecordCollection = $this->subRecordRepository->getRecordsByParentId($record->getId());

        foreach ($subRecordCollection->getItems() as $subRecord) {
            $this->transaction->addObject($subRecord);
        }

        $this->transaction->addObject($record);

        $this->writeDeleteLog($record, $subRecordCollection);

        $this->transaction->delete();
    }

    protected function writeDeleteLog(HifiSalesReportRecord $record, SubRecordCollection $subRecordCollection): void
    {
        $subRecordCollection = $this->subRecordRepository->getRecordsByParentId($record->getId());

        $subRecordsArray = [];

        foreach ($subRecordCollection as $subRecord) {
            $subRecordsArray[] = $subRecord->toArray();
        }

        $timezone = new \DateTimeZone('Asia/Taipei');
        $datetime = new \DateTime('now', $timezone);
        $datetime->format('Y-m-d\TH:i:sP');

        $logContent = json_encode([
            "Deleter Admin ID"   => $this->authSession->getUser()->getId(),
            "Deleter Admin name" => $this->authSession->getUser()->getUserName(),
            "Main Record"        => $record->toArray(),
            "Sub Records"        => $subRecordsArray,
            "Datetime"           => $datetime->format('Y-m-d\TH:i:sP'),
            "Timestamp"          => time(),
        ]);

        $this->hotaiCoreCommonHelper->writeLog(
            $logContent,
            self::LOG_NAME,
            self::LOG_FILE_NAME
        );
    }

    protected function redirect(string $message): Redirect
    {
        $this->messageManager->addWarning(__($message));

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->getUrl('*/display/records'));
        return $resultRedirect;
    }
}
