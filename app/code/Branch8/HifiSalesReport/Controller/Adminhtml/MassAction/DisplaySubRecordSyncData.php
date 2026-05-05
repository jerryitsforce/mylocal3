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

class DisplaySubRecordSyncData extends Action implements HttpPostActionInterface
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
        $this->initParameters();

        $this->displaySyncRequestingData();
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
     * 顯示同步HIFI請求資料
     * @return void
     */
    protected function displaySyncRequestingData()
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

        die(json_encode($postData));
    }

    protected function getFilteredMainFilePostString(SubRecordModel $subRecord): string
    {
        $mainRecord = $this->mainRecordRepository->get($subRecord->getParentId());

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

    protected function getFilteredDetailFilePostString(SubRecordModel $subRecord): string
    {
        $mainRecord = $this->mainRecordRepository->get($subRecord->getParentId());

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
}
