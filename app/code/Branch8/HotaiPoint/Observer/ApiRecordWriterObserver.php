<?php

namespace Branch8\HotaiPoint\Observer;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;
use Branch8\HotaiPoint\Model\HotaiPointApiRecord as ApiRecordModel;
use Branch8\HotaiPoint\Model\HotaiPointApiRecordFactory;
use Branch8\HotaiPoint\Model\HotaiPointApiRecordRepository;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ApiRecordWriterObserver implements ObserverInterface
{
    const LOG_FOLDER_NAME = 'Observer';
    private const LOG_SUBFOLDER_NAME = 'ApiRecordWriterObserver';
    private const DEBUG_LOG_OPTION = LogOption::LOG_API_RECORD_WRITER_OBSERVER;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var HotaiPointApiRecordFactory */
    protected $apiRecordFactory;

    /** @var HotaiPointApiRecordRepository */
    protected $apiRecordRepository;

    protected $logFileName;

    public function __construct(
        ApiHelper $apiHelper,
        CommonHelper $commonHelper,
        HotaiPointApiRecordFactory $apiRecordFactory,
        HotaiPointApiRecordRepository $apiRecordRepository
    ) {
        $this->apiHelper           = $apiHelper;
        $this->commonHelper        = $commonHelper;
        $this->apiRecordFactory    = $apiRecordFactory;
        $this->apiRecordRepository = $apiRecordRepository;

        $this->logFileName = "api_record_writer_observer_" . date("Y_m_d") . ".log";
    }

    public function execute(Observer $observer)
    {
        $eventName = $observer->getEvent()->getName();

        switch ($eventName) {
            case ApiHelper::API_SUCCESS_EVENT_DEDUCTION_POINT:
                $this->handleDeductionPoint($observer);
                break;
            case ApiHelper::API_SUCCESS_EVENT_DEDUCTION_POINT_BY_GET_ORDER_INFO:
                $this->handleDeductionPointByGetOrderInfo($observer);
                break;
            case ApiHelper::API_SUCCESS_EVENT_ADD_N_DEDUCTION_POINT:
                $this->handleAddAndDeductionPoint($observer);
                break;
            case ApiHelper::API_SUCCESS_EVENT_ADD_POINT:
                $this->handleAddPoint($observer);
                break;
            case ApiHelper::API_SUCCESS_EVENT_BATCH_ADD_POINT:
                $this->handleBatchAddPoint($observer);
                break;
            case ApiHelper::API_SUCCESS_EVENT_RETURN_POINT:
                $this->handleReturnPoint($observer);
                break;
            case ApiHelper::API_SUCCESS_EVENT_ADD_POINT_NON:
                $this->handleAddPointNon($observer);
                break;
            case ApiHelper::API_SUCCESS_EVENT_COMMIT:
                $this->handleCommit($observer);
                break;
            default:
                $this->writeLog(json_encode([
                    "Title"      => "Unexpected event name.",
                    "Event name" => $eventName,
                ]));
                break;
        }
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function handleDeductionPoint(Observer $observer): void
    {
        $requestData           = $observer->getData("requestData");
        $responseData          = $observer->getData("responseData");
        $requestTaiwanDatetime = $observer->getData("requestTaiwanDatetime");

        $apiRecord = $this->apiRecordFactory->create();
        $apiRecord->setTransType(ApiRecordModel::TRANS_TYPE_DEDUCTION_POINT);
        $apiRecord->setTraceNo($this->apiHelper->getTraceNoFromResponse($responseData));
        $apiRecord->setBuNo($requestData["buNo"]);
        $apiRecord->setOneid($requestData["oneid"]);
        $apiRecord->setMemberAccount($requestData["memberAccount"]);
        $apiRecord->setOneidType($requestData["oneidType"]);
        $apiRecord->setRsNo($requestData["rsNo"]);
        $apiRecord->setPosNo($requestData["posNo"]);
        $apiRecord->setTransSN($requestData["transSN"]);
        $apiRecord->setTransDatetime($requestTaiwanDatetime);
        $apiRecord->setTransDesc($requestData["transDesc"]);
        $apiRecord->setDeductionPoint($requestData["deductionPoint"]);

        $this->apiRecordRepository->save($apiRecord);

        $this->writeLog(json_encode([
            "Title"      => "ApiRecordWriterObserver handleDeductionPoint done.",
            "EventData"  => $observer->getData(),
            "RecordData" => $apiRecord->toArray(),
        ]));
    }

    public function handleDeductionPointByGetOrderInfo(Observer $observer): void
    {
        $requestData           = $observer->getData("requestData");
        $traceNo               = $observer->getData("traceNo");
        $requestTaiwanDatetime = $observer->getData("requestTaiwanDatetime");

        $apiRecord = $this->apiRecordRepository->getRecordByTraceNo($traceNo);

        if (!is_null($apiRecord)) {
            $this->writeLog(json_encode([
                "Title"      => "ApiRecordWriterObserver handleDeductionPointByGetOrderInfo record already exists.",
                "EventData"  => $observer->getData(),
                "RecordData" => $apiRecord->toArray(),
            ]));

            return;
        }

        $apiRecord = $this->apiRecordFactory->create();
        $apiRecord->setTransType(ApiRecordModel::TRANS_TYPE_DEDUCTION_POINT);
        $apiRecord->setTraceNo($traceNo);
        $apiRecord->setBuNo($requestData["buNo"]);
        $apiRecord->setOneid($requestData["oneid"]);
        $apiRecord->setMemberAccount($requestData["memberAccount"]);
        $apiRecord->setOneidType($requestData["oneidType"]);
        $apiRecord->setRsNo($requestData["rsNo"]);
        $apiRecord->setPosNo($requestData["posNo"]);
        $apiRecord->setTransSN($requestData["transSN"]);
        $apiRecord->setTransDatetime($requestTaiwanDatetime);
        $apiRecord->setTransDesc($requestData["transDesc"]);
        $apiRecord->setDeductionPoint($requestData["deductionPoint"]);

        $this->apiRecordRepository->save($apiRecord);

        $this->writeLog(json_encode([
            "Title"      => "ApiRecordWriterObserver handleDeductionPointByGetOrderInfo done.",
            "EventData"  => $observer->getData(),
            "RecordData" => $apiRecord->toArray(),
        ]));
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function handleAddAndDeductionPoint(Observer $observer): void {}

    /**
     * @param Observer $observer
     * @return void
     */
    public function handleAddPoint(Observer $observer): void
    {
        $requestData           = $observer->getData("requestData");
        $responseData          = $observer->getData("responseData");
        $requestTaiwanDatetime = $observer->getData("requestTaiwanDatetime");

        $apiRecord = $this->apiRecordFactory->create();
        $apiRecord->setTransType(ApiRecordModel::TRANS_TYPE_ADD_POINT);
        $apiRecord->setTraceNo($this->apiHelper->getTraceNoFromResponse($responseData));
        $apiRecord->setBuNo($requestData["buNo"]);
        $apiRecord->setOneid($requestData["oneid"]);
        $apiRecord->setMemberAccount($requestData["memberAccount"]);
        $apiRecord->setOneidType($requestData["oneidType"]);
        $apiRecord->setRsNo($requestData["rsNo"]);
        $apiRecord->setPosNo($requestData["posNo"]);
        $apiRecord->setTransSN($requestData["transSN"]);
        $apiRecord->setTransDatetime($requestTaiwanDatetime);
        $apiRecord->setTransDesc($requestData["transDesc"]);
        $apiRecord->setAddType($requestData["addType"]);
        $apiRecord->setAmt($requestData["amt"]);
        $apiRecord->setPointAmt($requestData["pointAmt"]);

        if (isset($requestData["activityCodes"])) {
            $apiRecord->setActivityCodes($requestData["activityCodes"]);
        }

        $this->apiRecordRepository->save($apiRecord);

        $this->writeLog(json_encode([
            "Title"      => "ApiRecordWriterObserver handleAddPoint done.",
            "EventData"  => $observer->getData(),
            "RecordData" => $apiRecord->toArray(),
        ]));
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function handleBatchAddPoint(Observer $observer): void {}

    /**
     * @param Observer $observer
     * @return void
     */
    public function handleReturnPoint(Observer $observer): void
    {
        $orderItem             = $observer->getData("orderItem");
        $customer              = $observer->getData("customer");
        $sourceTraceNo         = $observer->getData("sourceTraceNo");
        $requestData           = $observer->getData("requestData");
        $responseData          = $observer->getData("responseData");
        $requestTaiwanDatetime = $observer->getData("requestTaiwanDatetime");

        $apiRecord = $this->apiRecordFactory->create();
        $apiRecord->setTransType(ApiRecordModel::TRANS_TYPE_RETURN_POINT);
        $apiRecord->setTraceNo($this->apiHelper->getTraceNoFromResponse($responseData));
        $apiRecord->setBuNo($requestData["buNo"]);
        $apiRecord->setOneid($responseData["data"]["oneid"]);
        $apiRecord->setMemberAccount($customer->getCustomAttribute('member_seq')->getValue());
        $apiRecord->setOneidType(ApiHelper::ONEID_TYPE);
        $apiRecord->setTransSN($requestData["transSN"]);
        $apiRecord->setTransDatetime($requestTaiwanDatetime);
        $apiRecord->setSourceTraceNo($sourceTraceNo);

        $this->apiRecordRepository->save($apiRecord);

        $this->writeLog(json_encode([
            "Title"      => "ApiRecordWriterObserver handleReturnPoint done.",
            "EventData"  => $observer->getData(),
            "RecordData" => $apiRecord->toArray(),
        ]));
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function handleAddPointNon(Observer $observer): void
    {
        $requestData           = $observer->getData("requestData");
        $responseData          = $observer->getData("responseData");
        $requestTaiwanDatetime = $observer->getData("requestTaiwanDatetime");

        $apiRecord = $this->apiRecordFactory->create();
        $apiRecord->setTransType(ApiRecordModel::TRANS_TYPE_ADD_POINT_NON);
        $apiRecord->setTraceNo($this->apiHelper->getTraceNoFromResponse($responseData));
        $apiRecord->setBuNo($requestData["buNo"]);
        $apiRecord->setOneid($requestData["oneid"]);
        $apiRecord->setMemberAccount($requestData["memberAccount"]);
        $apiRecord->setOneidType($requestData["oneidType"]);
        $apiRecord->setRsNo($requestData["rsNo"]);
        $apiRecord->setPosNo($requestData["posNo"]);
        $apiRecord->setTransSN($requestData["transSN"]);
        $apiRecord->setTransDatetime($requestTaiwanDatetime);
        $apiRecord->setTransDesc($requestData["transDesc"]);
        $apiRecord->setAddPoint($requestData["addPoint"]);
        $apiRecord->setPointValidType($requestData["pointValidType"]);

        if (isset($requestData["pointValidDate"])) {
            $apiRecord->setPointValidDate($requestData["pointValidDate"]);
        }

        $this->apiRecordRepository->save($apiRecord);

        $this->writeLog(json_encode([
            "Title"      => "ApiRecordWriterObserver handleAddPointNon done.",
            "EventData"  => $observer->getData(),
            "RecordData" => $apiRecord->toArray(),
        ]));
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function handleCommit(Observer $observer): void
    {
        $requestData           = $observer->getData("requestData");
        $requestTaiwanDatetime = $observer->getData("requestTaiwanDatetime");
        $traceNo               = $requestData["sourceTraceNo"];

        $apiRecord = $this->apiRecordRepository->getRecordByTraceNo($traceNo);

        if (is_null($apiRecord)) {
            throw new \Exception("Can't locate api record row by given traceNo: " . $traceNo);
        }

        $apiRecord->setCommitStatus(ApiRecordModel::COMMIT_STATUS_COMMITED);

        if (empty($apiRecord->getCommitDatetime())) {
            $apiRecord->setCommitDatetime($requestTaiwanDatetime);
        }

        $this->apiRecordRepository->save($apiRecord);

        $this->writeLog(json_encode([
            "Title"      => "ApiRecordWriterObserver handleCommit done.",
            "EventData"  => $observer->getData(),
            "RecordData" => $apiRecord->toArray(),
        ]));
    }

    private function writeLog($message)
    {
        $this->commonHelper->writeLogIfEnabled(
            $message,
            self::LOG_FOLDER_NAME . '/' . self::LOG_SUBFOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }
}
