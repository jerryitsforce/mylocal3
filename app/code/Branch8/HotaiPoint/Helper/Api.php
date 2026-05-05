<?php

namespace Branch8\HotaiPoint\Helper;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Branch8\HotaiPoint\Helper\Curl;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;
use Branch8\HotaiPoint\Model\HotaiPointApiRecord as HotaiPointApiRecordModel;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\HotaiPoint\Model\TransferPointHistoryFactory;
use Branch8\HotaiPoint\Model\TransferPointHistoryRepository;
use Branch8\HotaiPoint\Model\UsingCouponHistoryFactory;
use Branch8\HotaiPoint\Model\UsingCouponHistoryRepository;

class Api
{
    private const LOG_SUBFOLDER_NAME = 'Api/ApiRequestError';
    private const DEBUG_LOG_OPTION = LogOption::LOG_API;
    private const DEBUG_LOG_OPTION_INTEGRATION = LogOption::LOG_API_INTEGRATION;
    private const DEBUG_LOG_OPTION_GET_POINT_DATA = LogOption::LOG_API_GET_POINT_DATA;

    const API_ROUTE_GET_POINT_BY_ONEID       = "GetPointByOneid";
    const API_ROUTE_GET_TRANS_INFO           = "GetTransInfo";
    const API_ROUTE_ADD_POINT                = "AddPoint";
    const API_ROUTE_COMMIT                   = "Commit";
    const API_ROUTE_ADD_POINT_NON            = "AddPointNon";
    const API_ROUTE_DEDUCTION_POINT          = "DeductionPoint";
    const API_ROUTE_CANCEL                   = "Cancel";
    const API_ROUTE_RETURN_POINT             = "ReturnPoint";
    const API_ROUTE_GET_RETURN_FAILURE_POINT = "GetReturnFailurePoint";
    const API_ROUTE_TRANSFER_POINT           = "TransferPoint";
    const API_ROUTE_GET_MEMBER_OTP_SEND      = "GetMemberOTPSend";
    const API_ROUTE_USING_COUPON             = "UsingCoupon";
    const API_ROUTE_UNLOCK_ONEID             = "UnLockOneId";
    const API_ROUTE_GET_ORDER_INFO           = "GetOrderInfo";

    const API_SUCCESS_EVENT_DEDUCTION_POINT = "hotai_point_api_success_deduction_point";
    const API_SUCCESS_EVENT_DEDUCTION_POINT_BY_GET_ORDER_INFO = "hotai_point_api_success_deduction_point_by_get_order_info";
    const API_SUCCESS_EVENT_ADD_N_DEDUCTION_POINT = "hotai_point_api_success_add_n_deduction_point";
    const API_SUCCESS_EVENT_ADD_POINT = "hotai_point_api_success_add_point";
    const API_SUCCESS_EVENT_BATCH_ADD_POINT = "hotai_point_api_success_batch_add_point";
    const API_SUCCESS_EVENT_RETURN_POINT = "hotai_point_api_success_return_point";
    const API_SUCCESS_EVENT_ADD_POINT_NON = "hotai_point_api_success_add_point_non";
    const API_SUCCESS_EVENT_COMMIT = "hotai_point_api_success_commit";

    const ONEID_TYPE                      = "P";
    const ADD_TYPE                        = "1";
    const RETURN_TYPE                     = "1";
    const GET_DUE_POINT_BY_ONE_ID_VERSION = 3;

    const QUERY_TYPE_TOTAL                  = 1; // 總點數
    const QUERY_TYPE_TOTAL_AND_POINT_DETAIL = 2; // 總點數+點數明細
    const QUERY_TYPE_TOTAL_AND_ADD_DETAIL   = 3; // 總點數+累點交易明細
    const QUERY_TYPE_TOTAL_AND_DEDUC_DETAIL = 4; // 總點數+兌點交易明細

    const ENCRYPT_METHOD = "AES-256-CBC";

    const API_RESPONSE_CODE_SUCCESS                          = "0000";
    const API_RESPONSE_CODE_TRANSACTION_LOCK                 = "9002";
    const API_RESPONSE_CODE_GET_TRANS_INFO_NO_DATA           = "0107";
    const API_RESPONSE_CODE_DEDUCTION_POINT_NOT_ENOUGH_POINT = "0304";
    const API_RESPONSE_CODE_ORDER_STATUS_DEDUCTION_MADE      = "2";
    const API_RESPONSE_CODE_ORDER_STATUS_COMMIT_SUCCESS      = "3";

    const DEFAULT_PAGE_INDEX = 1;
    const DEFAULT_PAGE_SIZE  = 10;
    const PAGE_SIZE_POOL     = [10, 20, 30, 40, 50, 100];

    const CURL_TIMEOUT_ERROR_CODE  = 28;
    const CURL_TIMEOUT_SECONDS     = 40;
    const CURL_TIMEOUT_RETRY_LIMIT = 3;

    const LOG_FOLDER_NAME                 = 'Api';
    const LOG_FOLDER_NAME_FOR_INTEGRATION = 'HotaiPoint/Api/Integration';
    const LOG_FOLDER_NAME_FOR_GET_POINT   = 'HotaiPoint/Api/GetPoint';
    const API_ROUTES_GET_POINT_DATA       = [
        self::API_ROUTE_GET_POINT_BY_ONEID,
        self::API_ROUTE_GET_TRANS_INFO
    ];

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var Curl */
    protected $curl;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var OrderItemRepositoryInterface */
    protected $orderItemRepository;

    /** @var ManagerInterface */
    protected $eventManager;

    /** @var TransferPointHistoryFactory */
    protected $transferPointHistoryFactory;

    /** @var TransferPointHistoryRepository */
    protected $transferPointHistoryRepository;

    /** @var UsingCouponHistoryFactory */
    protected $usingCouponHistoryFactory;

    /** @var UsingCouponHistoryRepository */
    protected $usingCouponHistoryRepository;

    protected $fireApiRecordEvent = true;
    protected $apiPath;
    protected $curlHeader;
    protected $curlBody;
    protected $requestDataArray;
    protected $requestDataString;
    protected $requestTimesData;
    protected $lastResponse;
    protected $lastResponseStatus;

    protected $logFileName;

    protected $token;
    protected $memberSeq;
    protected $memberAccount;
    protected $buNo;
    protected $rsNo;
    protected $posNo;
    protected $curlRetryLimit;

    protected $defaultHeaderUserAgent = null;

    protected $useFrontendFlow      = false;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        Curl $curl,
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        ManagerInterface $eventManager,
        TransferPointHistoryFactory $transferPointHistoryFactory,
        TransferPointHistoryRepository $transferPointHistoryRepository,
        UsingCouponHistoryFactory $usingCouponHistoryFactory,
        UsingCouponHistoryRepository $usingCouponHistoryRepository
    ) {
        $this->customerRepository             = $customerRepository;
        $this->hotaiCoreCommonHelper          = $hotaiCoreCommonHelper;
        $this->commonHelper                   = $commonHelper;
        $this->curl                           = $curl;
        $this->orderRepository                = $orderRepository;
        $this->orderItemRepository            = $orderItemRepository;
        $this->eventManager                   = $eventManager;
        $this->transferPointHistoryFactory    = $transferPointHistoryFactory;
        $this->transferPointHistoryRepository = $transferPointHistoryRepository;
        $this->usingCouponHistoryFactory      = $usingCouponHistoryFactory;
        $this->usingCouponHistoryRepository   = $usingCouponHistoryRepository;

        $this->requestDataArray  = [];
        $this->requestDataString = "";
        $this->requestTimesData  = [];

        $this->logFileName = "api_request_error_" . date("Y_m_d") . ".log";

        $this->buNo  = $this->hotaiCoreCommonHelper->getHotaiCoreConfig(HotaiCoreCommonHelper::HOTAI_CORE_CONFIG_PATH_BU_NO);
        $this->rsNo  = $this->hotaiCoreCommonHelper->getHotaiCoreConfig(HotaiCoreCommonHelper::HOTAI_CORE_CONFIG_PATH_RS_NO);
        $this->posNo = $this->hotaiCoreCommonHelper->getHotaiCoreConfig(HotaiCoreCommonHelper::HOTAI_CORE_CONFIG_PATH_POS_NO);

        $userAgentConfig = $this->hotaiCoreCommonHelper->getHotaiCoreConfig(
            HotaiCoreCommonHelper::HOTAI_CORE_CONFIG_PATH_CURL_HEADER_USER_AGENT
        );
        if (!empty($userAgentConfig)) {
            $this->defaultHeaderUserAgent = $userAgentConfig;
        }
    }

    /**
     * 請求2.1 會員點數查詢
     * 使用情境: 提供查詢會員帳號點數及其點數交易明細
     * @param int $pageIndex
     * @param int $pageSize
     * @return array
     */
    public function requestApiGetPointByOneid(
        int $customerId,
        int $queryType = self::QUERY_TYPE_TOTAL_AND_POINT_DETAIL,
        int $pageIndex = self::DEFAULT_PAGE_INDEX,
        int $pageSize = self::DEFAULT_PAGE_SIZE,
        \DateTime $startDate = null,
        \DateTime $endDate = null
    ): array {
        if (!is_numeric($pageIndex) || $pageIndex <= 0) {
            $pageIndex = self::DEFAULT_PAGE_INDEX;
        }

        if (!in_array($pageSize, self::PAGE_SIZE_POOL)) {
            $pageSize = self::DEFAULT_PAGE_SIZE;
        }

        $requestDataArray = [
            "oneid"     => $this->getOneIdByCustomerId($customerId),
            "oneidType" => self::ONEID_TYPE,
            "queryType" => $queryType,
            "pageSize"  => $pageSize, // queryType=2~4必填; 10, 20, 30, 40, 50, 100
            "pageIndex" => $pageIndex, // queryType=2~4必填; 從1開始
        ];


        if (!empty($startDate)) {
            $requestDataArray["startDate"] = $startDate->format('Ymd');
        }

        if (!empty($endDate)) {
            $requestDataArray["endDate"] = $endDate->format('Ymd');
        }

        $response              = $this->sendRequest(self::API_ROUTE_GET_POINT_BY_ONEID, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString) && !$this->validateNoTransHistory($decryptResponseString)) {
            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting GetPointByOneid.",
                "Customer ID"                    => $customerId,
                "Customer name"                  => "",
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        return json_decode($decryptResponseString, true);
    }

    /**
     * 傳入customerId向和泰API查詢最近月份到期點數
     * example return: $resultArray = json_decode("[{"date":"20241231","point":81},{"date":"20251231","point":350},{"date":"20260930","point":100}]", true);
     * @param int $customerId
     * @param mixed $dateSort = SORT_ASC or SORT_DESC
     * @throws \Exception
     * @return array
     */
    public function getRecentMonthsDuePointsByCustomerId(int $customerId, $dateSort = SORT_ASC): array
    {
        $requestDataArray = [
            "oneid"     => $this->getOneIdByCustomerId($customerId),
            "oneidType" => self::ONEID_TYPE,
            "queryType" => self::QUERY_TYPE_TOTAL,
            "version"   => self::GET_DUE_POINT_BY_ONE_ID_VERSION
        ];

        $response              = $this->sendRequest(self::API_ROUTE_GET_POINT_BY_ONEID, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString)) {
            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting GetPointByOneid(getRecentMonthsDuePointsByCustomerId).",
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        $responseArray = json_decode($decryptResponseString, true);
        $resultArray   = [];

        if (!empty($responseArray["data"]["expireDate"])) {
            $resultArray[] = [
                "date"  => $responseArray["data"]["expireDate"],
                "point" => $responseArray["data"]["expirePoint"] ?? 0
            ];
        }

        if (isset($responseArray["data"]["expireDetail"]) && is_array($responseArray["data"]["expireDetail"])) {
            foreach ($responseArray["data"]["expireDetail"] as $expireData) {
                if (!isset($expireData["dateByExpire"])) {
                    continue;
                }

                $resultArray[] = [
                    "date"  => $expireData["dateByExpire"],
                    "point" => $expireData["pointByExpire"] ?? 0
                ];
            }
        }

        // Just sort by "date" column.
        array_multisort(array_column($resultArray, "date"), $dateSort, $resultArray);

        return $resultArray;
    }

    /**
     * 請求2.1 交易資訊查詢
     * 取得用戶當下點數
     *
     * @param integer $customerId
     * @return array
     */
    public function requestApiGetTransInfo(int $customerId): array
    {
        $requestDataArray = [
            "buNo"      => $this->buNo,
            "oneid"     => $this->getOneIdByCustomerId($customerId),
            "oneidType" => self::ONEID_TYPE,
            "version"   => 2,
        ];

        $response              = $this->sendRequest(self::API_ROUTE_GET_TRANS_INFO, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString) && !$this->validateNoTransHistory($decryptResponseString)) {
            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting GetTransInfo.",
                "Customer ID"                    => $customerId,
                "Customer name"                  => "",
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        // decryptResponseString:
        // {"returnCode":"0000","returnMsg":null,"data":{"oneid":"041bb652-feed-440b-832d-e58c33e15088","point":100,"discountLimitType":1,"discountLmit":20}}

        return json_decode($decryptResponseString, true);
    }

    /**
     * 請求2.6 即時累點
     *
     * @param integer $customerId
     * @param string $transSN
     * @param int|string $transTimestamp
     * @param integer $amt
     * @param string $transDesc
     * @return array
     */
    public function requestApiAddPoint(int $customerId, string $transSN, int|string $transTimestamp, int $amt, string $transDesc): array
    {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $taiwanDateObj->setTimestamp($transTimestamp);

        $requestDataArray = [
            "buNo"          => $this->buNo,
            "oneid"         => $this->getOneIdByCustomerId($customerId),
            "memberAccount" => $this->getMemberAccountByCustomerId($customerId),
            "oneidType"     => self::ONEID_TYPE,
            "rsNo"          => $this->rsNo,
            "posNo"         => $this->posNo,
            "transSN"       => $transSN,
            // "transDate"     => date("Ymd", \strtotime($transDatetime)),
            // "transTime"     => date("His", \strtotime($transDatetime)),
            "transDate"     => $taiwanDateObj->format("Ymd"),
            "transTime"     => $taiwanDateObj->format("His"),
            "transDesc"     => $transDesc,
            "addType"       => self::ADD_TYPE,
            "amt"           => $amt,
            "pointAmt"      => $amt,
            // "activityCodes" => "",
        ];

        $response              = $this->sendRequest(self::API_ROUTE_ADD_POINT, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString)) {
            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting AddPoint.",
                "Customer ID"                    => "",
                "Customer name"                  => "",
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        // decryptResponseString:
        // {"returnCode":"0000","returnMsg":null,"data":{"traceNo":"O231123000000013","oneid":"041bb652-feed-440b-832d-e58c33e15088","addPoint":20,"point":30}}

        $responseDataArray = json_decode($decryptResponseString, true);

        // Only write api record table when api is certain success.
        if ($this->validateApiResponse($decryptResponseString) && $this->fireApiRecordEvent) {
            $this->eventManager->dispatch(
                self::API_SUCCESS_EVENT_ADD_POINT,
                [
                    "requestData"           => $this->requestDataArray,
                    "responseData"          => $responseDataArray,
                    "requestTaiwanDatetime" => $taiwanDateObj->format("Y-m-d H:i:s")
                ]
            );
        }

        return $responseDataArray;
    }

    /**
     * 請求2.11 無金額即時累點
     * @param int $customerId
     * @param int $addPoint
     * @param string $transSN
     * @param int|string $transTimestamp
     * @param string $transDesc
     * @param int|string $pointValidType // HotaiPointApiRecordModel::POINT_VALID_TYPE_NORMAL or HotaiPointApiRecordModel::POINT_VALID_TYPE_CUSTOM
     * @param string $pointValidDate // pass in +8 date example: 202509
     * @throws \Exception
     * @return array
     */
    public function requestApiAddPointNon(
        int $customerId,
        int $addPoint,
        string $transSN,
        int|string $transTimestamp,
        string $transDesc = "",
        int|string $pointValidType = HotaiPointApiRecordModel::POINT_VALID_TYPE_NORMAL,
        string $pointValidDate = null,
        string $buNo = "",
        string $rsNo = ""
    ): array {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $taiwanDateObj->setTimestamp($transTimestamp);

        $requestBuNo = empty($buNo) ? $this->buNo : $buNo;
        $requestRsNo = empty($rsNo) ? $this->rsNo : $rsNo;

        $requestDataArray = [
            "buNo"           => $requestBuNo,
            "oneid"          => $this->getOneIdByCustomerId($customerId),
            "memberAccount"  => $this->getMemberAccountByCustomerId($customerId),
            "oneidType"      => self::ONEID_TYPE,
            "rsNo"           => $requestRsNo,
            "posNo"          => $this->posNo,
            "transSN"        => $transSN,
            // "transDate"      => date("Ymd", \strtotime($transDatetime)),
            // "transTime"      => date("His", \strtotime($transDatetime)),
            "transDate"      => $taiwanDateObj->format("Ymd"),
            "transTime"      => $taiwanDateObj->format("His"),
            "transDesc"      => mb_substr($transDesc, 0, 48, 'UTF-8'),
            "addType"        => self::ADD_TYPE,
            "addPoint"       => $addPoint,
            "pointValidType" => $pointValidType,
        ];

        $this->checkPointValidParametersForAddPointNon(
            $customerId,
            $pointValidType,
            $pointValidDate
        );

        if ($pointValidType == HotaiPointApiRecordModel::POINT_VALID_TYPE_CUSTOM) {
            $requestDataArray["pointValidDate"] = $pointValidDate;
        }

        $response              = $this->sendRequest(self::API_ROUTE_ADD_POINT_NON, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString)) {
            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting AddPointNon.",
                "Customer ID"                    => "",
                "Customer name"                  => "",
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        // Only write api record table when api is certain success.
        if ($this->validateApiResponse($decryptResponseString) && $this->fireApiRecordEvent) {
            $this->eventManager->dispatch(
                self::API_SUCCESS_EVENT_ADD_POINT_NON,
                [
                    "requestData"           => $this->requestDataArray,
                    "responseData"          => json_decode($decryptResponseString, true),
                    "requestTaiwanDatetime" => $taiwanDateObj->format("Y-m-d H:i:s")
                ]
            );
        }

        // decryptResponseString:
        // {"returnCode":"0000","returnMsg":null,"data":{"traceNo":"O231123000000015","oneid":"041bb652-feed-440b-832d-e58c33e15088","addPoint":100,"point":130}}

        return json_decode($decryptResponseString, true);
    }

    protected function checkPointValidParametersForAddPointNon(
        int $customerId,
        int|string $pointValidType,
        null|string $pointValidDate
    ) {
        if ($pointValidType != HotaiPointApiRecordModel::POINT_VALID_TYPE_NORMAL && $pointValidType != HotaiPointApiRecordModel::POINT_VALID_TYPE_CUSTOM) {
            $this->writeLog(json_encode([
                "Title"                    => "Point valid parameters error.",
                "Customer ID"              => $customerId,
                "Pass in point valid type" => $pointValidType,
                "Pass in point valid date" => $pointValidDate,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"   => "Point valid parameters error.",
                "Timestamp" => time(),
            ]));
        }

        if ($pointValidType == HotaiPointApiRecordModel::POINT_VALID_TYPE_CUSTOM && empty($pointValidDate)) {
            $this->writeLog(json_encode([
                "Title"                    => "Point valid parameters error.",
                "Customer ID"              => $customerId,
                "Pass in point valid type" => $pointValidType,
                "Pass in point valid date" => $pointValidDate,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"   => "Point valid parameters error.",
                "Timestamp" => time(),
            ]));
        }
    }

    /**
     * 請求2.2 即時兌點
     *
     * @param integer $customerId
     * @param string $transSN
     * @param integer $amt
     * @param float $deductionPoint
     * @param string $transDesc
     * @return array
     */
    public function requestApiDeductionPoint(
        int $customerId,
        string $transSN,
        int|string $transTimestamp,
        float $amt,
        float $deductionPoint,
        string $transDesc,
        array $allowResponseCode = [self::API_RESPONSE_CODE_SUCCESS]
    ): array {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $taiwanDateObj->setTimestamp($transTimestamp);

        $requestDataArray = [
            "buNo"           => $this->buNo,
            "oneid"          => $this->getOneIdByCustomerId($customerId),
            "memberAccount"  => $this->getMemberAccountByCustomerId($customerId),
            "oneidType"      => self::ONEID_TYPE,
            "rsNo"           => $this->rsNo,
            "posNo"          => $this->posNo,
            "transSN"        => $transSN,
            "transDate"      => $taiwanDateObj->format("Ymd"),
            "transTime"      => $taiwanDateObj->format("His"),
            "transDesc"      => $transDesc,
            "amt"            => $amt,
            "deductionPoint" => $deductionPoint,
        ];

        $response              = $this->sendRequest(self::API_ROUTE_DEDUCTION_POINT, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString, $allowResponseCode)) {
            $logJson = json_encode([
                "Title"                          => "Something went wrong while requesting DeductionPoint.",
                "Customer ID"                    => $customerId,
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES);

            if (empty($logJson)) {
                $this->writeLog("TransSN: {$transSN}, Customer ID: {$customerId}");
                $this->writeLog("TransSN: {$transSN}, API path: {$this->apiPath}");
                $this->writeLog("TransSN: {$transSN}, Request header: " . json_encode($this->curlHeader));
                foreach ($requestDataArray as $key => $value) {
                    $this->writeLog("TransSN: {$transSN}, Request data field: {$key}, Request data value: {$value}");
                }
                $this->writeLog("TransSN: {$transSN}, Response string before decrypt: " . json_encode($response));
                $this->writeLog("TransSN: {$transSN}, Response string after decrypt: {$decryptResponseString}");
            } else {
                $this->writeLog($logJson);
            }

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        // decryptResponseString:
        // {"returnCode":"0000","returnMsg":null,"data":{"traceNo":"O231123000000016","oneid":"041bb652-feed-440b-832d-e58c33e15088","blockPoint":30}}
        $responseDataArray = json_decode($decryptResponseString, true);

        // Only write api record table when api is certain success.
        if ($this->validateApiResponse($decryptResponseString) && $this->fireApiRecordEvent) {
            $this->eventManager->dispatch(
                self::API_SUCCESS_EVENT_DEDUCTION_POINT,
                [
                    "requestData"           => $this->requestDataArray,
                    "responseData"          => $responseDataArray,
                    "requestTaiwanDatetime" => $taiwanDateObj->format("Y-m-d H:i:s")
                ]
            );
        }

        return $responseDataArray;
    }

    /**
     * 請求2.4 完成交易
     *
     * @param int $customerId
     * @param string $traceNo
     * @param array $allowResponseCode
     * @throws \Exception
     * @return array
     */
    public function requestApiCommit(
        int $customerId,
        string $traceNo,
        array $allowResponseCode = [self::API_RESPONSE_CODE_SUCCESS]
    ): array {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $taiwanDateObj->setTimestamp(time());

        $requestDataArray = [
            "buNo"          => $this->buNo,
            "oneid"         => $this->getOneIdByCustomerId($customerId),
            "memberAccount" => $this->getMemberAccountByCustomerId($customerId),
            "oneidType"     => self::ONEID_TYPE,
            "sourceTraceNo" => $traceNo,
        ];

        $response              = $this->sendRequest(self::API_ROUTE_COMMIT, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString, $allowResponseCode)) {
            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting Commit.",
                "Customer ID"                    => $customerId,
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        // decryptResponseString:
        // {"returnCode":"0000","returnMsg":null,"data":{"oneid":"041bb652-feed-440b-832d-e58c33e15088","addPoint":20,"deductionPoint":0,"point":30}}
        // {"returnCode":"0000","returnMsg":null,"data":{"oneid":"041bb652-feed-440b-832d-e58c33e15088","addPoint":0,"deductionPoint":30,"point":100}}

        $responseDataArray = json_decode($decryptResponseString, true);

        // Only write api record table when api is certain success.
        if ($this->validateApiResponse($decryptResponseString) && $this->fireApiRecordEvent) {
            $this->eventManager->dispatch(
                self::API_SUCCESS_EVENT_COMMIT,
                [
                    "requestData"           => $this->requestDataArray,
                    "requestTaiwanDatetime" => $taiwanDateObj->format("Y-m-d H:i:s")
                ]
            );
        }

        return $responseDataArray;
    }

    /**
     * 請求2.5 取消累兌點圈存
     *
     * @param integer $customerId
     * @param string $traceNo
     * @return array
     */
    public function requestApiCancel(
        int $customerId,
        string $traceNo,
        array $allowResponseCode = [self::API_RESPONSE_CODE_SUCCESS]
    ): array {
        $requestDataArray = [
            "buNo"          => $this->buNo,
            "oneid"         => $this->getOneIdByCustomerId($customerId),
            "memberAccount" => $this->getMemberAccountByCustomerId($customerId),
            "oneidType"     => self::ONEID_TYPE,
            "sourceTraceNo" => $traceNo,
        ];

        $response              = $this->sendRequest(self::API_ROUTE_CANCEL, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString, $allowResponseCode)) {
            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting Cancel.",
                "Customer ID"                    => $customerId,
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        // decryptResponseString:
        // ...

        return json_decode($decryptResponseString, true);
    }

    /**
     * 請求2.7 即時退貨 (for order_item)
     *
     * @param integer $orderItemId
     * @return array
     */
    public function requestApiReturnPointByOrderItemId(
        int $orderItemId,
        array $allowResponseCode = [self::API_RESPONSE_CODE_SUCCESS]
    ): array {
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $orderItem = $this->orderItemRepository->get($orderItemId);
        $order     = $this->orderRepository->get($orderItem->getOrderId());
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer      = $this->customerRepository->getById($order->getCustomerId());
        $sourceTraceNo = $orderItem->getData("hotai_point_deduction_point_trace_no");
        $transSN       = $orderItem->getData("hotai_point_deduction_point_trans_s_n");
        $transDatetime = $orderItem->getData("hotai_point_deduction_point_trans_datetime");

        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $taiwanDateObj->setTimestamp(strtotime("{$transDatetime} Asia/Taipei"));

        $requestTaiwanDateObj = new \DateTime();
        $requestTaiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $requestTaiwanDateObj->setTimestamp(time());

        $requestDataArray = [
            "buNo"       => $this->buNo,
            // "transDate"  => date("Ymd", \strtotime($transDatetime)),
            // "transTime"  => date("His", \strtotime($transDatetime)),
            "transDate"  => $taiwanDateObj->format("Ymd"),
            "transTime"  => $taiwanDateObj->format("His"),
            "transSN"    => $transSN,
            "returnType" => self::RETURN_TYPE,
            // "couponNo"   => "",
        ];

        $response              = $this->sendRequest(self::API_ROUTE_RETURN_POINT, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString, $allowResponseCode)) {
            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting ReturnPoint.",
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        // decryptResponseString:
        // {"returnCode":"0000","returnMsg":null,"data":{"traceNo":"R240106000000003","transSN":"20240105164323","oneid":"041bb652-feed-440b-832d-e58c33e15088","point":107}}

        $responseDataArray = json_decode($decryptResponseString, true);

        // Only write api record table when api is certain success.
        if ($this->validateApiResponse($decryptResponseString) && $this->fireApiRecordEvent) {
            $this->eventManager->dispatch(
                self::API_SUCCESS_EVENT_RETURN_POINT,
                [
                    "orderItem"             => $orderItem,
                    "customer"              => $customer,
                    "sourceTraceNo"         => $sourceTraceNo,
                    "requestData"           => $this->requestDataArray,
                    "responseData"          => $responseDataArray,
                    "requestTaiwanDatetime" => $requestTaiwanDateObj->format("Y-m-d H:i:s")
                ]
            );
        }

        return $responseDataArray;
    }

    /**
     * 請求2.7 即時退貨
     *
     * @param string $transSN
     * @param string $transDatetime
     * @param array $allowResponseCode
     * @return array
     */
    public function requestApiReturnPoint(
        int $customerId,
        string $transSN,
        string $transDatetime,
        string $sourceTraceNo,
        array $allowResponseCode = [self::API_RESPONSE_CODE_SUCCESS]
    ): array {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $taiwanDateObj->setTimestamp(strtotime("{$transDatetime} Asia/Taipei"));

        $requestTaiwanDateObj = new \DateTime();
        $requestTaiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $requestTaiwanDateObj->setTimestamp(time());

        $requestDataArray = [
            "buNo"       => $this->buNo,
            "transDate"  => $taiwanDateObj->format("Ymd"),
            "transTime"  => $taiwanDateObj->format("His"),
            "transSN"    => $transSN,
            "returnType" => self::RETURN_TYPE,
        ];

        $response              = $this->sendRequest(self::API_ROUTE_RETURN_POINT, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString, $allowResponseCode)) {
            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting ReturnPoint (for quote_item).",
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        // decryptResponseString:
        // {"returnCode":"0000","returnMsg":null,"data":{"traceNo":"R240106000000003","transSN":"20240105164323","oneid":"041bb652-feed-440b-832d-e58c33e15088","point":107}}

        $responseDataArray = json_decode($decryptResponseString, true);

        // Only write api record table when api is certain success.
        if ($this->validateApiResponse($decryptResponseString) && $this->fireApiRecordEvent) {
            $customer = $this->customerRepository->getById($customerId);

            $this->eventManager->dispatch(
                self::API_SUCCESS_EVENT_RETURN_POINT,
                [
                    "orderItem"             => null,
                    "customer"              => $customer,
                    "sourceTraceNo"         => $sourceTraceNo,
                    "requestData"           => $this->requestDataArray,
                    "responseData"          => $responseDataArray,
                    "requestTaiwanDatetime" => $requestTaiwanDateObj->format("Y-m-d H:i:s")
                ]
            );
        }

        return $responseDataArray;
    }

    /**
     * 請求2.12 退貨前失效點數查詢
     * (這必須是已成立的點數交易否則會有以下訊息)
     * {"returnCode":"0309","returnMsg":"該交易未完成，請先完成交易或取消交易","data":null}
     * @param int $orderItemId
     * @throws \Exception
     * @return array
     */
    public function requestApiGetReturnFailurePoint(int $orderItemId): array
    {
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $orderItem     = $this->orderItemRepository->get($orderItemId);
        $transSN       = $orderItem->getData("hotai_point_deduction_point_trans_s_n");
        $transDatetime = $orderItem->getData("hotai_point_deduction_point_trans_datetime");

        if (empty($transSN) || empty($transDatetime)) {
            throw new \Exception("Mandatory data for requesting ReturnFailurePoint is missing.");
        }

        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $taiwanDateObj->setTimestamp(strtotime("{$transDatetime} Asia/Taipei"));

        $requestDataArray = [
            "buNo"      => $this->buNo,
            // "transDate" => date("Ymd", \strtotime($transDatetime)),
            // "transTime" => date("His", \strtotime($transDatetime)),
            "transDate" => $taiwanDateObj->format("Ymd"),
            "transTime" => $taiwanDateObj->format("His"),
            "transSN"   => $transSN,
        ];

        $response              = $this->sendRequest(self::API_ROUTE_GET_RETURN_FAILURE_POINT, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString)) {
            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting GetReturnFailurePoint.",
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        // response example:
        // {"returnCode":"0000","returnMsg":null,"data":{"traceNo":"O241018000000032","transSN":"000002465_16972_1729234779","totalDeductionPoint":5,"nonFailurePoint":5,"failurePoint":0}}

        $responseDataArray = json_decode($decryptResponseString, true);

        return $responseDataArray;
    }

    /**
     * 請求2.13 查詢訂單交易資料
     * @param string $transSN
     * @param string|int $transTimestamp
     * @return array
     */
    public function requestApiGetOrderInfo(string $transSN, string|int $transTimestamp): array
    {
        /** @var \DateTime $taiwanDateObj */
        $taiwanDateObj = $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject();
        $taiwanDateObj->setTimestamp($transTimestamp);

        $requestDataArray = [
            "buNo"      => $this->buNo,
            "transDate" => $taiwanDateObj->format("Ymd"),
            "transTime" => $taiwanDateObj->format("His"),
            "transSN"   => $transSN,
        ];

        $response              = $this->sendRequest(self::API_ROUTE_GET_ORDER_INFO, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        // response example:
        // (orderStatus: 2 means deduction made, 3 means commit success)
        // {"returnCode":"0000","returnMsg":null,"data":{"traceNo":"O250822000000606","transSN":"24764_33783_1755847250","oneid":"041bb652-feed-440b-832d-e58c33e15088","orderStatus":3,"transDateTime":"2025\/08\/22 15:20:50","amt":1,"pointAmt":0,"totalPoint":0,"totalDeductionPoint":1}}

        $responseDataArray = json_decode($decryptResponseString, true);

        return $responseDataArray;
    }

    public function requestApiTransferPoint(
        int $fromCustomerId,
        string $toCustomerOneid,
        string $toCustomerMemberAccount,
        int|string $transferPoint,
        string $transferCheckCode
    ): array {
        $functionInput = [
            "fromCustomerId"          => $fromCustomerId,
            "toCustomerOneid"         => $toCustomerOneid,
            "toCustomerMemberAccount" => $toCustomerMemberAccount,
            "transferPoint"           => $transferPoint,
            "checkCode"             => $transferCheckCode
        ];

        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));

        $requestDataArray = [
            "oneid"         => $this->getOneIdByCustomerId($fromCustomerId),
            "memberAccount" => $this->getMemberAccountByCustomerId($fromCustomerId),
            "transSN"       => $this->generateTransSNForTransferPoint($fromCustomerId),
            "transDate"     => $taiwanDateObj->format("Ymd"),
            "transTime"     => $taiwanDateObj->format("His"),
            "moveOutPoint"  => $transferPoint,
            "moveInOneid"   => $toCustomerOneid,
            "moveInAccount" => $toCustomerMemberAccount,
            "checkCode"     => $transferCheckCode
        ];

        if ($transferCheckCode === "") {
            try {
                $this->requestApiGetMemberOTPSend($fromCustomerId);
            } catch (\Exception $e) {
                $smsReturn = json_decode($e->getMessage(), true);
            }
            if (isset($smsReturn['Response string after decrypt'])) {
                $checkStatus = json_decode($smsReturn['Response string after decrypt'], true);
                if (isset($checkStatus['returnCode']) && $checkStatus['returnCode'] == "0339") {
                    $requestDataArray = [
                        "oneid"         => $this->getOneIdByCustomerId($fromCustomerId),
                        "memberAccount" => $this->getMemberAccountByCustomerId($fromCustomerId),
                        "transSN"       => $this->generateTransSNForTransferPoint($fromCustomerId),
                        "transDate"     => $taiwanDateObj->format("Ymd"),
                        "transTime"     => $taiwanDateObj->format("His"),
                        "moveOutPoint"  => $transferPoint,
                        "moveInOneid"   => $toCustomerOneid,
                        "moveInAccount" => $toCustomerMemberAccount
                    ];
                }
            }
        }

        $response              = $this->sendRequest(self::API_ROUTE_TRANSFER_POINT, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString)) {
            // 寫失敗記錄到資料表 hotai_point_transfer_point_history
            $transferPointHistory = $this->transferPointHistoryFactory->create();
            $transferPointHistory->setFromCustomerId($fromCustomerId);
            $transferPointHistory->setFromCustomerOneid($requestDataArray["oneid"]);
            $transferPointHistory->setFromCustomerMemberAccount($requestDataArray["memberAccount"]);
            $transferPointHistory->setToCustomerOneid($requestDataArray["moveInOneid"]);
            $transferPointHistory->setToCustomerMemberAccount($requestDataArray["moveInAccount"]);
            $transferPointHistory->setTransferPoint($requestDataArray["moveOutPoint"]);
            $transferPointHistory->setTransSN($requestDataArray["transSN"]);
            $transferPointHistory->setTransAt($taiwanDateObj->setTimezone(new \DateTimeZone("UTC"))->format("Y-m-d H:i:s"));
            $transferPointHistory->setIsSuccess(0);
            $transferPointHistory->setMemo(json_encode([
                "Function input" => $functionInput,
                "Request data"   => $requestDataArray,
                "Response data"  => $decryptResponseString,
            ]));
            $this->transferPointHistoryRepository->save($transferPointHistory);

            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting TransferPoint.",
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        // response example:
        // {"returnCode":"0000","returnMsg":null,"data":{"traceNo":"TP250221000000001","oneid":"bed00a9d-1839-420c-93a8-433cc7c744c6","moveOutPoint":5,"point":38410,"expirePoint":0,"expireDate":"20250228"}}

        // 寫成功記錄到資料表 hotai_point_transfer_point_history
        $transferPointHistory = $this->transferPointHistoryFactory->create();
        $transferPointHistory->setFromCustomerId($fromCustomerId);
        $transferPointHistory->setFromCustomerOneid($requestDataArray["oneid"]);
        $transferPointHistory->setFromCustomerMemberAccount($requestDataArray["memberAccount"]);
        $transferPointHistory->setToCustomerOneid($requestDataArray["moveInOneid"]);
        $transferPointHistory->setToCustomerMemberAccount($requestDataArray["moveInAccount"]);
        $transferPointHistory->setTransferPoint($requestDataArray["moveOutPoint"]);
        $transferPointHistory->setTransSN($requestDataArray["transSN"]);
        $transferPointHistory->setTransAt($taiwanDateObj->setTimezone(new \DateTimeZone("UTC"))->format("Y-m-d H:i:s"));
        $transferPointHistory->setIsSuccess(1);
        $transferPointHistory->setMemo(json_encode([
            "Function input" => $functionInput,
            "Request data"   => $requestDataArray,
            "Response data"  => $decryptResponseString,
        ]));
        $this->transferPointHistoryRepository->save($transferPointHistory);

        $responseDataArray = json_decode($decryptResponseString, true);

        return $responseDataArray;
    }

    /**
     * 請求2.15 發送會員交易 OTP 驗證
     * @param int $customerId
     * @return array
     */
    public function requestApiGetMemberOTPSend(
        int $customerId
    ): array {
        $requestDataArray = [
            "buNo"      => $this->buNo,
            "oneid"         => $this->getOneIdByCustomerId($customerId),
            "memberAccount" => $this->getMemberAccountByCustomerId($customerId),
            "RequestFlag" => 1,
        ];

        $response              = $this->sendRequest(self::API_ROUTE_GET_MEMBER_OTP_SEND, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString)) {

            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting GetMemberOTPSend.",
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        $responseDataArray = json_decode($decryptResponseString, true);

        return $responseDataArray;
    }

    public function requestApiUsingCoupon(
        int $customerId,
        string $couponNo
    ): array {
        $functionInput = [
            "customerId" => $customerId,
            "couponNo"   => $couponNo
        ];

        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));

        $requestDataArray = [
            "oneid"         => $this->getOneIdByCustomerId($customerId),
            "memberAccount" => $this->getMemberAccountByCustomerId($customerId),
            "oneidType"     => self::ONEID_TYPE,
            "transSN"       => $this->generateTransSNForUsingCoupon($customerId),
            "transDate"     => $taiwanDateObj->format("Ymd"),
            "transTime"     => $taiwanDateObj->format("His"),
            "couponNo"      => $couponNo
        ];

        $response              = $this->sendRequest(self::API_ROUTE_USING_COUPON, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        if (!$this->validateApiResponse($decryptResponseString)) {
            // 寫失敗記錄到資料表 hotai_point_using_coupon_history
            $usingCouponHistory = $this->usingCouponHistoryFactory->create();
            $usingCouponHistory->setCustomerId($customerId);
            $usingCouponHistory->setTransSN($requestDataArray["transSN"]);
            $usingCouponHistory->setTransAt($taiwanDateObj->setTimezone(new \DateTimeZone("UTC"))->format("Y-m-d H:i:s"));
            $usingCouponHistory->setCouponNo($couponNo);
            $usingCouponHistory->setIsSuccess(0);
            $usingCouponHistory->setMemo(json_encode([
                "Function input" => $functionInput,
                "Request data"   => $requestDataArray,
                "Response data"  => $decryptResponseString,
            ]));
            $this->usingCouponHistoryRepository->save($usingCouponHistory);

            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting UsingCoupon.",
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        // response example:
        // {"returnCode":"0000","returnMsg":"","data":{"traceNo":"O20210502000005","oneid":"ca576a4a-69b1-46d7-8ae3-ebe1214fd747","addPoint":50,"point":605}}

        // 寫成功記錄到資料表 hotai_point_using_coupon_history
        $usingCouponHistory = $this->usingCouponHistoryFactory->create();
        $usingCouponHistory->setCustomerId($customerId);
        $usingCouponHistory->setTransSN($requestDataArray["transSN"]);
        $usingCouponHistory->setTransAt($taiwanDateObj->setTimezone(new \DateTimeZone("UTC"))->format("Y-m-d H:i:s"));
        $usingCouponHistory->setCouponNo($couponNo);
        $usingCouponHistory->setIsSuccess(1);
        $usingCouponHistory->setMemo(json_encode([
            "Function input" => $functionInput,
            "Request data"   => $requestDataArray,
            "Response data"  => $decryptResponseString,
        ]));
        $this->usingCouponHistoryRepository->save($usingCouponHistory);

        $responseDataArray = json_decode($decryptResponseString, true);

        return $responseDataArray;
    }

    public function requestApiUnlockOneId(
        int $customerId,
        bool $needValidate = false
    ): array {
        $requestDataArray = [
            "oneid" => $this->getOneIdByCustomerId($customerId)
        ];

        $response              = $this->sendRequest(self::API_ROUTE_UNLOCK_ONEID, $requestDataArray);
        $decryptResponseString = $this->decryptApiResponseIfUseFrontendFlow($response);

        // 不要直接驗證丟出例外, 方便在retry cron中留下記錄
        if ($needValidate && !$this->validateApiResponse($decryptResponseString)) {
            $this->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting UnlockOneId.",
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request string after encrypt"   => $this->curlBody,
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ], \JSON_UNESCAPED_SLASHES));

            throw new \Exception(json_encode([
                "Message"                        => "Something went wrong while requesting hotai point api.",
                "Response string before decrypt" => $response,
                "Response status code"           => $this->lastResponseStatus,
                "Response string after decrypt"  => $decryptResponseString,
            ]));
        }

        // response example:
        // {"returnCode":"0000","returnMsg":""}

        $responseDataArray = json_decode($decryptResponseString, true);

        return $responseDataArray;
    }

    public function generateTransSNForTransferPoint(int $fromCustomerId)
    {
        $transSN = "{$fromCustomerId}_" . $this->hotaiCoreCommonHelper->getRandomString(4) . "_" . time();

        return mb_substr($transSN, 0, 30, 'UTF-8');
    }

    public function generateTransSNForUsingCoupon(int $customerId)
    {
        $transSN = "{$customerId}_" . $this->hotaiCoreCommonHelper->getRandomString(4) . "_" . time();

        return mb_substr($transSN, 0, 30, 'UTF-8');
    }

    /**
     * 傳入orderItemId向和泰API查詢最近退貨可取回的點數資訊
     *
     * Return data example:
     * $resultArray = json_decode("{"traceNo":"O241018000000032","transSN":"000002465_16972_1729234779","totalDeductionPoint":5,"nonFailurePoint":5,"failurePoint":0}", true);
     *
     * Return data field explanation:
     * traceNo: transaction number from Hotai Point.
     * transSN: transaction number from Magento.
     * totalDeductionPoint: means how many points this transaction has in total.
     * nonFailurePoint: means how many points customer will get if this transaction get returned.
     * failurePoint: means how many points customer will lose if this transaction get returned.
     *
     * @param int $orderItemId
     * @throws \Exception
     * @return array
     */
    public function getReturnFailurePointInfoByOrderItemId(int $orderItemId): array
    {
        $response = $this->requestApiGetReturnFailurePoint($orderItemId);

        return $this->getDataFromResponse($response);
    }

    /**
     * 從回傳資料中找到data欄位
     * @param array $response
     * @return array
     */
    public function getDataFromResponse(array $response): array
    {
        if (!isset($response["data"])) {
            throw new \Exception("Can't locate data from input response, json encoded response: " . json_encode($response));
        }

        return $response["data"];
    }

    /**
     * 從累兌點回傳資料中找到returnCode欄位
     *
     * @param array $response
     * @return string
     */
    public function getReturnCodeFromResponse(array $response): string
    {
        if (!isset($response["returnCode"])) {
            throw new \Exception("Can't locate returnCode from input response, json encoded response: " . json_encode($response));
        }

        return $response["returnCode"];
    }

    /**
     * 從累兌點回傳資料中找到traceNo欄位
     *
     * @param array $response
     * @return string
     */
    public function getTraceNoFromResponse(array $response): string
    {
        if (!isset($response["data"]["traceNo"])) {
            throw new \Exception("Can't locate traceNo from input response, json encoded response: " . json_encode($response));
        }

        return $response["data"]["traceNo"];
    }

    /**
     * 從查詢用戶點數回傳資料中找到point欄位
     *
     * @param array $response
     * @return string
     */
    public function getPointFromResponse(array $response): string
    {
        if (isset($response["returnCode"]) && $response["returnCode"] == self::API_RESPONSE_CODE_GET_TRANS_INFO_NO_DATA) {
            return 0;
        }

        if (!isset($response["data"]["point"])) {
            throw new \Exception("Can't locate point from input response, json encoded response: " . json_encode($response));
        }

        return $response["data"]["point"];
    }

    /**
     * 從查詢用戶點數回傳資料中找到point欄位
     *
     * @param array $response
     * @return string
     */
    public function getExpirePointFromResponse(array $response): string
    {
        if (!isset($response["data"]["expirePoint"])) {
            throw new \Exception("Can't locate expire point from input response, json encoded response: " . json_encode($response));
        }

        return $response["data"]["expirePoint"];
    }

    /**
     * 變更 $this->fireApiRecordEvent 的狀態(預設為true)
     * 參數值為true則對和泰點數API進行點數交易請求後會將請求資料寫入資料庫紀錄
     * (app/code/Branch8/HotaiPoint/Model/HotaiPointApiRecord.php)
     * (app/code/Branch8/HotaiPoint/Observer/ApiRecordWriterObserver.php)
     * 用於製作和泰每日交易總檔
     * (app/code/Branch8/HotaiPoint/Cron/SyncApiRecord.php)
     *
     * @param boolean $fireApiRecordEvent
     * @return void
     */
    public function setFireApiRecordEvent(bool $fireApiRecordEvent): void
    {
        $this->fireApiRecordEvent = $fireApiRecordEvent;
    }

    /**
     * 獲取 $this->requestDataArray 參數值
     * 主要用於查看最近一筆加密前API請求資料(array形式)
     *
     * @return array
     */
    public function getRequestDataArray(): array
    {
        return [
            "requestDataArray" => $this->requestDataArray,
            "requestTimesData" => $this->requestTimesData,
        ];
    }

    /**
     * 獲取 $this->requestDataString 參數值
     * 主要用於查看最近一筆加密前API請求資料(string形式)
     *
     * @return string
     */
    public function getRequestDataString(): string
    {
        return json_encode([
            "requestDataString" => $this->requestDataString,
            "requestTimesData"  => $this->requestTimesData,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function getRequestTimesData(): array
    {
        return $this->requestTimesData;
    }

    /**
     * 獲取 $this->lastResponse 參數值
     * 主要用於查看最近一筆API回傳資料(string形式)
     *
     * @return string
     */
    public function getLastResponse(): string
    {
        return $this->lastResponse;
    }

    /**
     * 獲取 $this->lastResponseStatus 參數值
     * 主要用於查看最近一筆API HTTP狀態碼
     *
     * @return int|null
     */
    public function getLastResponseStatus(): ?int
    {
        return $this->lastResponseStatus;
    }

    /**
     * 設定是否使用前台會員請求模式(在header代入會員token, 請求與回傳進行額外加解密)
     *
     * @param boolean $useFrontendFlow
     * @return boolean
     */
    public function setUseFrontendFlow(bool $useFrontendFlow): bool
    {
        $this->useFrontendFlow = $useFrontendFlow;

        return $this->useFrontendFlow;
    }

    /**
     * 設定前台會員請求流程所需的會員資料
     *
     * @param string $token
     * @param string $memberSeq
     * @param string $memberAccount
     * @return boolean
     */
    public function setFrontendFlowData(string $token, string $memberSeq, string $memberAccount): bool
    {
        $this->token         = $token;
        $this->memberSeq     = $memberSeq;
        $this->memberAccount = $memberAccount;

        $this->setUseFrontendFlow(true);

        return $this->useFrontendFlow;
    }

    /**
     * 送出curl請求
     *
     * @param string $apiRoute
     * @param array $requestDataArray
     * @return string
     */
    private function sendRequest(string $apiRoute, array $requestDataArray): string
    {
        // Reset prarmeters
        $this->apiPath            = null;
        $this->curlHeader         = null;
        $this->requestDataArray   = null;
        $this->requestDataString  = null;
        $this->requestTimesData   = [];
        $this->curlBody           = null;
        $this->lastResponse       = null;
        $this->lastResponseStatus = null;

        // Set api path.
        $domain        = $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_API_DOMAIN);
        $domain        = rtrim($domain, "/");
        $this->apiPath = "{$domain}/{$apiRoute}";

        // Set header.
        if ($this->useFrontendFlow) {
            $this->curlHeader = [
                "APP_ID"        => $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_APP_ID),
                "APP_VERSION"   => $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_APP_VERSION),
                "Authorization" => "Bearer " . $this->token,
                "Content-Type"  => "application/json",
            ];
        } else {
            $this->curlHeader = [
                "APP_ID"       => $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_APP_ID),
                "APP_KEY"      => $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_APP_KEY),
                "Content-Type" => "application/json",
            ];
        }

        // Set user agent header.
        if (!isset($this->curlHeader["User-Agent"]) && !empty($this->defaultHeaderUserAgent)) {
            $this->curlHeader["User-Agent"] = $this->defaultHeaderUserAgent;
        }
        // ------------------------------------------------

        // Set request data(before encrypt).
        $this->requestDataArray  = $requestDataArray;
        $this->requestDataString = json_encode($this->requestDataArray, JSON_UNESCAPED_UNICODE);
        // ------------------------------------------------

        // Set curl body
        $this->curlBody = json_encode($this->requestDataArray);

        if ($this->useFrontendFlow) {
            $this->curlBody = $this->encryptApiRequestString($this->curlBody);
        }
        // ------------------------------------------------

        $this->curl->setHeaders($this->curlHeader);
        $this->curl->setTimeout(self::CURL_TIMEOUT_SECONDS);

        // 若curl逾時則以相同參數重送直到重試次數上限
        $retryLimit = $this->getCurlRetryLimit();
        for ($retryCount = 1; $retryCount <= $retryLimit; $retryCount++) {
            try {
                $this->requestTimesData[] = [
                    "title"           => "Ready to send request",
                    "retryCount"      => $retryCount,
                    "requestTime(+8)" => $this->getTaiwanDateTimeWithMicroSec(),
                ];

                $this->curl->post($this->apiPath, $this->curlBody);

                break;
            } catch (\Exception $e) {
                $this->requestTimesData[] = [
                    "title"           => "Exception while sending request",
                    "retryCount"      => $retryCount,
                    "requestTime(+8)" => $this->getTaiwanDateTimeWithMicroSec(),
                    "curlErrno"       => $this->curl->getErrno()
                ];

                if ($this->isTimeoutException() && !$this->isCurlRetryHitLimit($retryCount)) {
                    continue;
                }

                throw $e;
            }
        }

        $this->lastResponse       = $this->curl->getBody();
        $this->lastResponseStatus = $this->curl->getStatus();

        $this->writeApiLogIfNeeded($apiRoute);

        return $this->lastResponse;
    }

    public function reset(): void
    {
        $this->apiPath           = null;
        $this->curlHeader        = null;
        $this->requestDataArray  = [];
        $this->requestDataString = "";
        $this->curlBody          = null;
        $this->lastResponse      = "";
        $this->lastResponseStatus = null;
    }

    /**
     * 根據傳入的order和order item資料回傳提供給兌點API的TransSN欄位內容
     *
     * @param Order $order
     * @param OrderItem $orderItem
     * @return string
     */
    public function generateTransSNForApiDeductionPoint(Order $order, OrderItem $orderItem): string
    {
        return $order->getQuoteId() . "_" . $orderItem->getQuoteItemId() . "_" . time();
    }

    /**
     * 根據傳入的order和order item資料回傳提供給兌點API的TransDesc欄位內容
     *
     * @param Order $order
     * @param OrderItem $orderItem
     * @return string
     */
    public function generateTransDescForApiDeductionPoint(Order $order, OrderItem $orderItem): string
    {
        $productName = $this->sanitizeTransDescText((string) $orderItem->getName());
        $productName = mb_substr($productName, 0, 40, 'UTF-8');
        $qtyDesc     = ' ' . (int) $orderItem->getQtyOrdered() . '個';

        return mb_substr($productName . $qtyDesc, 0, 48, 'UTF-8');
    }

    /**
     * 移除特殊字元與潛在 XSS / SQL Injection 字元
     *
     * @param string $text
     * @return string
     */
    protected function sanitizeTransDescText(string $text): string
    {
        $whiteList = (string) $this->commonHelper->getHotaiPointConfig(
            CommonHelper::HOTAI_POINT_CONFIG_PATH_TRANS_DESC_WHITE_LIST
        );

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $escapedWhiteList = preg_quote($whiteList, '/');
        $pattern = '/[^0-9A-Za-z\x{4E00}-\x{9FFF}' . $escapedWhiteList . ']/u';
        $text = preg_replace($pattern, '', $text);

        $text = preg_replace('/\s+/u', ' ', (string) $text);

        return trim((string) $text);
    }

    /**
     * 確認當前例外是否屬於逾時例外
     *
     * @return boolean
     */
    private function isTimeoutException(): bool
    {
        return $this->curl->getErrno() == self::CURL_TIMEOUT_ERROR_CODE;
    }

    /**
     * 確認重新請求嘗試次數是否已達到上限
     *
     * @param integer $currentRetryCount
     * @return boolean
     */
    private function isCurlRetryHitLimit(int $currentRetryCount): bool
    {
        return $currentRetryCount >= self::CURL_TIMEOUT_RETRY_LIMIT;
    }

    /**
     * 依照和泰點數API規則對請求資料進行加密
     *
     * @param string $requestDataArray
     * @return string
     */
    private function encryptApiRequestString(string $requestDataString): string
    {
        $aesKey = $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_AES_KEY);
        $aesIv  = $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_AES_IV);

        $aesString = openssl_encrypt($requestDataString, self::ENCRYPT_METHOD, $aesKey, OPENSSL_RAW_DATA, $aesIv);

        return json_encode(["aesString" => \base64_encode($aesString)], \JSON_UNESCAPED_SLASHES);
    }

    /**
     * 若是使用前台請求流程則依照和泰點數API規則對回傳資料進行解密
     *
     * @param string $responseString
     * @return string
     */
    private function decryptApiResponseIfUseFrontendFlow(string $responseString): string
    {
        if (!$this->useFrontendFlow) {
            return $responseString;
        }

        $aesKey = $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_AES_KEY);
        $aesIv  = $this->commonHelper->getHotaiPointConfig(CommonHelper::HOTAI_POINT_CONFIG_PATH_AES_IV);

        return openssl_decrypt(\base64_decode($responseString), self::ENCRYPT_METHOD, $aesKey, OPENSSL_RAW_DATA, $aesIv);
    }

    /**
     * 判斷API回傳的狀態碼是否成功
     * (returnCode是否等於0000, 或是傳入希望判斷過關的returnCode)
     *
     * @param string $decryptResponseString
     * @param array $allowReturnCode
     * @return boolean
     */
    private function validateApiResponse(
        string $decryptResponseString,
        array $allowReturnCode = [self::API_RESPONSE_CODE_SUCCESS]
    ): bool {
        $decryptDataAry = json_decode($decryptResponseString, true);

        if (!isset($decryptDataAry["returnCode"])) {
            return false;
        }

        if (!in_array($decryptDataAry["returnCode"], $allowReturnCode)) {
            return false;
        }

        return true;
    }

    /**
     * 獲取點數歷史(requestApiGetTransInfo)的特殊判斷 => 用戶是否"沒有"點數交易歷史(可能新帳號)
     * (returnCode是否等於0107)
     *
     * @param string $decryptResponseString
     * @return boolean
     */
    private function validateNoTransHistory(string $decryptResponseString): bool
    {
        $decryptDataAry = json_decode($decryptResponseString, true);

        if (!isset($decryptDataAry["returnCode"])) {
            return false;
        }

        if ($decryptDataAry["returnCode"] == self::API_RESPONSE_CODE_GET_TRANS_INFO_NO_DATA) {
            return true;
        }

        return false;
    }

    /**
     * 根據傳入的customerId取得oneId的值(等同會員資料庫的member_seq欄位)
     *
     * @param integer $customerId
     * @return string|null
     */
    private function getOneIdByCustomerId(int $customerId): string|null
    {
        $customer = $this->customerRepository->getById($customerId);
        return $customer->getCustomAttribute('member_seq')->getValue();
    }

    /**
     * 根據傳入的customerId取得memberAccount的值(等同會員資料庫的phone_number欄位)
     *
     * @param integer $customerId
     * @return string
     */
    private function getMemberAccountByCustomerId(int $customerId): string
    {
        $customer = $this->customerRepository->getById($customerId);
        return $customer->getCustomAttribute("phone_number")->getValue();
    }

    private function writeApiLogIfNeeded(string $apiRoute): void
    {
        if (!in_array($apiRoute, self::API_ROUTES_GET_POINT_DATA)) {
            $this->commonHelper->writeLogIfEnabled(
                json_encode([
                    "Request api path"               => $this->apiPath,
                    "Request header"                 => $this->curlHeader,
                    "Request data before encrypt"    => $this->requestDataArray,
                    "Request string after encrypt"   => $this->curlBody,
                    "Response string before decrypt" => $this->lastResponse,
                    "Response array before decrypt"  => json_decode($this->lastResponse ?? "", true),
                    "Response string after decrypt"  => $this->decryptApiResponseIfUseFrontendFlow($this->lastResponse),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                self::LOG_FOLDER_NAME_FOR_INTEGRATION,
                self::DEBUG_LOG_OPTION_INTEGRATION
            );
        }

        if (in_array($apiRoute, self::API_ROUTES_GET_POINT_DATA)) {
            $this->commonHelper->writeLogIfEnabled(
                json_encode([
                    "Request api path"               => $this->apiPath,
                    "Request header"                 => $this->curlHeader,
                    "Request data before encrypt"    => $this->requestDataArray,
                    "Request string after encrypt"   => $this->curlBody,
                    "Response string before decrypt" => $this->lastResponse,
                    "Response array before decrypt"  => json_decode($this->lastResponse ?? "", true),
                    "Response string after decrypt"  => $this->decryptApiResponseIfUseFrontendFlow($this->lastResponse),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                self::LOG_FOLDER_NAME_FOR_GET_POINT,
                self::DEBUG_LOG_OPTION_GET_POINT_DATA
            );
        }
    }

    /**
     * 寫入log
     *
     * @param string $message
     * @return void
     */
    private function writeLog(string $message): void
    {
        $this->commonHelper->writeLogIfEnabled(
            $message,
            self::LOG_SUBFOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }

    protected function getTaiwanDateTimeWithMicroSec(): string
    {
        $dateTimeString = sprintf('%.6f', microtime(true));
        $taiwanDateObj  = \DateTime::createFromFormat(
            'U.u',
            $dateTimeString
        );
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));

        return $taiwanDateObj->format("Y-m-d H:i:s.u");
    }

    public function setCurlRetryLimit(int $curlRetryLimit): void
    {
        $this->curlRetryLimit = $curlRetryLimit;
    }

    protected function getCurlRetryLimit(): int
    {
        return $this->curlRetryLimit ?? self::CURL_TIMEOUT_RETRY_LIMIT;
    }
}
