<?php

namespace Branch8\HotaiPoint\Helper;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Framework\Event\ManagerInterface as EventManager;

class ApiFlow
{
    protected ApiHelper $apiHelper;
    protected HotaiCoreCommonHelper $hotaiCoreCommonHelper;
    protected EventManager $eventManager;

    protected $walkthroughLog = [];

    public function __construct(
        ApiHelper $apiHelper,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        EventManager $eventManager
    ) {
        $this->apiHelper = $apiHelper;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->eventManager = $eventManager;
    }

    public function deductionFlow(
        int $customerId,
        string $transSN,
        int|string $transTimestamp,
        float $amt,
        float $requestPoint,
        string $transDesc
    ): array {
        $this->walkthroughLog = [];
        $this->writeWalkthroughLog("DeductionFlow start.");
        $result = [
            'isSuccess'      => false,
            'traceNo'        => '',
            'walkthroughLog' => '',
        ];
        $this->apiHelper->reset();
        $this->apiHelper->setCurlRetryLimit(1);

        try {
            for ($i = 1; $i <= $this->apiHelper::CURL_TIMEOUT_RETRY_LIMIT; $i++) {
                try {
                    $this->writeWalkthroughLog("DeductionFlow try count: {$i} - start.");
                    $this->writeWalkthroughLog("Ready to request DeductionPoint API.");

                    $deductionPointResponse = $this->apiHelper->requestApiDeductionPoint(
                        $customerId,
                        $transSN,
                        $transTimestamp,
                        $amt,
                        $requestPoint,
                        $transDesc
                    );

                    $this->writeWalkthroughLog("DeductionPoint API done, request data: " . $this->apiHelper->getRequestDataString());
                    $this->writeWalkthroughLog("DeductionPoint API done, response data: " . json_encode($deductionPointResponse ?? null, JSON_UNESCAPED_UNICODE));

                    if (!isset($deductionPointResponse['returnCode']) || $deductionPointResponse['returnCode'] != $this->apiHelper::API_RESPONSE_CODE_SUCCESS) {
                        throw new \Exception("DeductionPoint API return code is not success.");
                    }

                    $result['isSuccess']  = true;
                    $result['traceNo']    = $this->apiHelper->getTraceNoFromResponse($deductionPointResponse);
                    break;
                } catch (\Exception $e) {
                    $deductionPointResponseString = $this->apiHelper->getLastResponse();
                    $deductionPointResponse = json_decode($deductionPointResponseString, true);
                    $this->writeWalkthroughLog("DeductionFlow try count: {$i} - exception: " . $e->getMessage());
                    $this->writeWalkthroughLog("Exception block, last request data: " . $this->apiHelper->getRequestDataString());
                    $this->writeWalkthroughLog("Exception block, last response data: " . json_encode($deductionPointResponse ?? null, JSON_UNESCAPED_UNICODE));
                    $deductionPointRequestData = $this->apiHelper->getRequestDataArray()["requestDataArray"] ?? [];

                    // If return code is 9002, wait 30 seconds before next step.
                    if (isset($deductionPointResponse['returnCode']) && $deductionPointResponse['returnCode'] == $this->apiHelper::API_RESPONSE_CODE_TRANSACTION_LOCK) {
                        $this->writeWalkthroughLog("DeductionPoint API result is transaction lock, start waiting 30 seconds.");
                        sleep(30);
                        $this->writeWalkthroughLog("Wait 30 seconds for transaction lock handling finished.");
                    }

                    // Request GetOrderInfo API, check if deduction success.
                    $this->writeWalkthroughLog("Ready to request GetOrderInfo API.");
                    $getOrderInfoResponse = $this->apiHelper->requestApiGetOrderInfo($transSN, $transTimestamp);
                    $this->writeWalkthroughLog("GetOrderInfo API done, request data: " . $this->apiHelper->getRequestDataString());
                    $this->writeWalkthroughLog("GetOrderInfo API done, response data: " . json_encode($getOrderInfoResponse, JSON_UNESCAPED_UNICODE));
                    if ($this->checkDeductionSuccessByGetOrderInfo($getOrderInfoResponse)) {
                        $this->writeWalkthroughLog("GetOrderInfo API result is deduction success.");
                        $traceNo = $this->apiHelper->getTraceNoFromResponse($getOrderInfoResponse);

                        /** @var \DateTime $taiwanDateObj */
                        $taiwanDateObj = $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject();
                        $taiwanDateObj->setTimestamp($transTimestamp);
                        $this->eventManager->dispatch(
                            ApiHelper::API_SUCCESS_EVENT_DEDUCTION_POINT_BY_GET_ORDER_INFO,
                            [
                                "requestData" => $deductionPointRequestData,
                                "traceNo" => $traceNo,
                                "requestTaiwanDatetime" => $taiwanDateObj->format("Y-m-d H:i:s")
                            ]
                        );

                        $result['isSuccess'] = true;
                        $result['traceNo'] = $traceNo;
                        break;
                    }
                }
            }
        } finally {
            $this->apiHelper->setCurlRetryLimit($this->apiHelper::CURL_TIMEOUT_RETRY_LIMIT);
            $result['walkthroughLog'] = $this->walkthroughLog;
        }

        return $result;
    }

    protected function checkDeductionSuccessByGetOrderInfo(array $getOrderInfoResponse): bool
    {
        $cond1 = isset($getOrderInfoResponse['returnCode']) && $getOrderInfoResponse['returnCode'] == $this->apiHelper::API_RESPONSE_CODE_SUCCESS;
        $cond2 = isset($getOrderInfoResponse['data']['orderStatus']) && $getOrderInfoResponse['data']['orderStatus'] == $this->apiHelper::API_RESPONSE_CODE_ORDER_STATUS_DEDUCTION_MADE;

        return $cond1 && $cond2;
    }

    protected function writeWalkthroughLog(string|array $message): void
    {
        $datetimeObj = $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject();
        $this->walkthroughLog[] = [
            'datetime(+8)' => $datetimeObj->format("Y-m-d H:i:s.u"),
            'message'      => $message,
        ];
    }
}
