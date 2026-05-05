<?php

declare(strict_types=1);

namespace Branch8\HifiSalesReport\Controller\Adminhtml\MassAction;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\DB\Transaction;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action;
use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord as SubRecordModel;
use Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportSubRecord\Collection as SubRecordCollection;
use Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportSubRecord\CollectionFactory as HifiSalesReportSubRecordCollectionFactory;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecordRepository as MainRecordRepository;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Branch8\HifiSalesReport\Helper\Report as ReportHelper;
use Branch8\HifiSalesReport\Controller\Adminhtml\Create\Submit;
use Magento\Ui\Component\MassAction\Filter;
use Branch8\HifiSalesReport\Helper\Api as ApiHelper;

class SubRecordSync extends Action implements HttpPostActionInterface
{
    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var AuthSession */
    protected $authSession;

    /** @var Transaction */
    protected $transaction;

    /** @var HifiSalesReportSubRecordCollectionFactory */
    protected $subRecordCollectionFactory;

    /** @var MainRecordRepository */
    protected $mainRecordRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var ReportHelper */
    protected $reportHelper;

    /** @var MessageManager */
    protected $messageManager;

    /** @var array */
    protected $subRecordIds;

    /** @var SubRecordCollection */
    protected $subRecordCollection;

    /** @var Filter */
    protected $filter;

    /** @var ApiHelper */
    protected $apiHelper;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        AuthSession $authSession,
        Transaction $transaction,
        HifiSalesReportSubRecordCollectionFactory $subRecordCollectionFactory,
        MainRecordRepository $mainRecordRepository,
        CommonHelper $commonHelper,
        ReportHelper $reportHelper,
        MessageManager $messageManager,
        Filter $filter,
        ApiHelper $apiHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->hotaiCoreCommonHelper      = $hotaiCoreCommonHelper;
        $this->authSession                = $authSession;
        $this->transaction                = $transaction;
        $this->subRecordCollectionFactory = $subRecordCollectionFactory;
        $this->mainRecordRepository       = $mainRecordRepository;
        $this->commonHelper               = $commonHelper;
        $this->reportHelper               = $reportHelper;
        $this->messageManager             = $messageManager;
        $this->filter                     = $filter;
        $this->apiHelper                  = $apiHelper;

        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $this->initParameters();

            $this->syncFlow();

            $this->updateFlowForSuccess();

            $this->messageManager->addSuccess(__('Sync to HIFI success.'));
            $this->messageManager->addSuccess($this->apiHelper->lastResponse);
        } catch (\Exception $e) {
            $this->messageManager->addError(__('Something went wrong while syncing to HIFI.'));
            $this->messageManager->addError($e->getMessage());

            $this->updateFlowForFail();
        }

        return $this->returnPreviousPage();
    }

    /**
     * 參數初始化
     * @return void
     */
    protected function initParameters()
    {
        $this->subRecordCollection = $this->filter->getCollection($this->subRecordCollectionFactory->create());
    }

    /**
     * 執行HIFI拋轉流程
     * @return void
     */
    protected function syncFlow()
    {
        $postData = [];

        /** @var SubRecordModel $subRecord */
        foreach ($this->subRecordCollection->getItems() as $subRecord) {
            $postData[] = [
                "batchCode"         => $subRecord->getBatchCode(),
                "mainFileContent"   => $this->getFilteredMainFilePostString($subRecord),
                "detailFileContent" => $this->getFilteredDetailFilePostString($subRecord),
            ];
        }

        $this->apiHelper->requestPostHifi($postData);
    }

    protected function getFilteredMainFilePostString(SubRecordModel $subRecord): string
    {
        $mainRecord    = $this->mainRecordRepository->get($subRecord->getParentId());

        $fileContentArray = $this->commonHelper->getFileContent(
            CommonHelper::FILE_TYPE_MODIFIED_MAIN_FILE,
            $mainRecord->getModifiedMainFileName()
        );

        $fileContentArray = $this->commonHelper->getFilteredMainFileContent(
            $fileContentArray,
            $subRecord
        );

        return base64_encode(json_encode($fileContentArray));
    }

    protected function getInvoiceStatusLabel(int $invoiceStatus): string
    {
        switch ($invoiceStatus) {
            case 1:
                return ReportHelper::INVOICE_STATUS_LABEL_ISSUE;

            case 2:
                return ReportHelper::INVOICE_STATUS_LABEL_CANCEL;

            case 3:
                return ReportHelper::INVOICE_STATUS_LABEL_ALLOWANCES;

            case 4:
                return ReportHelper::INVOICE_STATUS_LABEL_NO_INVOICE;

            default:
                return "Fail mapping: {$invoiceStatus}";
        }
    }

    protected function getFilteredDetailFilePostString(SubRecordModel $subRecord): string
    {
        $mainRecord    = $this->mainRecordRepository->get($subRecord->getParentId());

        $fileContentArray = $this->commonHelper->getFileContent(
            CommonHelper::FILE_TYPE_MODIFIED_DETAIL_FILE,
            $mainRecord->getModifiedDetailFileName()
        );

        $fileContentArray = $this->commonHelper->getFilteredDetailFileContent(
            $fileContentArray,
            $subRecord
        );

        return base64_encode(json_encode($fileContentArray));
    }

    /**
     * 更新子紀錄同步成功狀態
     * @return void
     */
    protected function updateFlowForSuccess()
    {
        /** @var SubRecordModel $subRecord */
        foreach ($this->subRecordCollection->getItems() as $subRecord) {
            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $subRecord->getMemo(),
                [
                    "Title"        => "Sync success.",
                    "API response" => $this->apiHelper->lastResponse,
                    "Admin ID"     => $this->authSession->getUser()->getId(),
                    "Admin name"   => $this->authSession->getUser()->getUserName(),
                    "Datetime"     => date("Y-m-d H:i:s"),
                    "Timestamp"    => time(),
                ]
            );

            $subRecord->setSyncStatus(SubRecordModel::SYNC_STATUS_SYNCED);
            $subRecord->setSyncResult(SubRecordModel::SYNC_RESULT_SUCCESS);
            $subRecord->setMemo($memo);

            $this->transaction->addObject($subRecord);
        }

        $this->transaction->save();
    }

    /**
     * 更新子紀錄同步失敗狀態
     * @return void
     */
    protected function updateFlowForFail()
    {
        /** @var SubRecordModel $subRecord */
        foreach ($this->subRecordCollection->getItems() as $subRecord) {
            $memo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $subRecord->getMemo(),
                [
                    "Title"        => "Sync fail.",
                    "API response" => $this->apiHelper->lastResponse,
                    "Admin ID"     => $this->authSession->getUser()->getId(),
                    "Admin name"   => $this->authSession->getUser()->getUserName(),
                    "Datetime"     => date("Y-m-d H:i:s"),
                    "Timestamp"    => time(),
                ]
            );

            $subRecord->setSyncStatus(SubRecordModel::SYNC_STATUS_SYNCED);
            $subRecord->setSyncResult(SubRecordModel::SYNC_RESULT_ERROR);
            $subRecord->setMemo($memo);

            $this->transaction->addObject($subRecord);
        }

        $this->transaction->save();
    }

    /**
     * 引導回前頁
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function returnPreviousPage(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}
