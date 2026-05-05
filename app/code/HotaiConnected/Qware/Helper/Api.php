<?php

namespace HotaiConnected\Qware\Helper;

use HotaiConnected\Qware\Helper\Common as CommonHelper;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Helper\Curl;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use HotaiConnected\Qware\Service\EmailNotificationService;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Sales\Model\Order\Item;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;

class Api
{
    const LOG_FOLDER_NAME = 'Qware/Api';

    const API_ROUTE_GET_VOUCHER_INFO      = "Commodities/";
    const API_ROUTE_CREATE_ORDER          = "Orders/Create";
    const API_ROUTE_QUERY_TICKETS         = "Tickets/Multi";
    const API_ROUTE_QUERY_ORDER           = "Orders";
    const API_ROUTE_REFUND_IMMEDIATELY    = "Tickets/RefundImmediately";
    
    const QUANTITY_LIMIT = 10;

    const CURL_HEADER_CONTENT_TYPE = "application/x-www-form-urlencoded";

    const CURL_TIMEOUT_ERROR_CODE  = 28;
    const CURL_TIMEOUT_SECONDS     = 30;
    const CURL_TIMEOUT_RETRY_LIMIT = 3;

    const COMPANY_ID = '55384114';

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var OrderItemRepositoryInterface */
    protected $orderItemRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var Curl */
    protected $curl;

    /** @var EmailNotificationService */
    protected $emailNotificationService;

    protected $apiPath;
    protected $curlHeader;
    protected $curlBody;
    protected $requestDataArray;
    protected $requestDataString;
    protected $sslKey;
    protected $sslIv;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        ProductRepositoryInterface $productRepository,
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        CommonHelper $commonHelper,
        Curl $curl,
        EmailNotificationService $emailNotificationService
    ) {
        $this->scopeConfig           = $scopeConfig;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->productRepository     = $productRepository;
        $this->orderRepository       = $orderRepository;
        $this->orderItemRepository   = $orderItemRepository;
        $this->commonHelper          = $commonHelper;
        $this->curl                  = $curl;
        $this->emailNotificationService = $emailNotificationService;

        $this->requestDataArray  = [];
        $this->requestDataString = "";
    }

    // ==============================================
    // 外部 API 呼叫 functions
    // ==============================================

    /**
     * 根據Guid向安源票券API請求商品資料
     *
     * @param string $guid 商品GUID
     * @return array
     */
    public function requestApiGetVoucherInfo(string $guid): array
    {
        $this->curlHeader = [
            "Content-Type" => self::CURL_HEADER_CONTENT_TYPE,
        ];

        $requestDataArray = [
            "AppId"     => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_APP_ID),
            "AccessKey" => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_APP_KEY)
        ];

        $requestUrl = self::API_ROUTE_GET_VOUCHER_INFO . $guid;

        $response = $this->sendRequest($requestUrl, $requestDataArray);
        $statusCode = $response['status_code'];
        $responseBody = $response['body'];
    
        if ($statusCode !== 200) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"            => "Something went wrong while requesting GetVoucherInfo.",
                "Request api path" => $this->apiPath,
                "Request header"   => $this->curlHeader,
                "Request data"     => $this->curlBody,
                "Response string"  => $responseBody,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);
    
            throw new \Exception(json_encode([
                "Message"         => "Something went wrong while requesting Qware API.",
                "Response string" => $responseBody,
            ]));
        }
    
        $responseArray = json_decode($responseBody, true);
        return $responseArray;
    }

    /**
     * 根據orderItemId向安源票券API請求票券資料 (下訂單)
     * (用在訂單確定付款後)
     *
     * @param integer $orderItemId
     * @param integer $requestQuantity
     * @return array
     */
    public function requestApiGetMultiVouchers(int $orderItemId, int $requestQuantity = 0): array
    {
        $this->checkRequestQuantityForGetMultiVouchers($requestQuantity);

        /** @var Item $orderItem */
        $orderItem = $this->orderItemRepository->get($orderItemId);
        /** @var Product $product */
        $product = $this->productRepository->getById($orderItem->getProductId());
        
        // 取得商品的 qware_guid
        $qwareGuid = $product->getData(CommonHelper::ATTRIBUTE_CODE_QWARE_GUID);
        if (empty($qwareGuid)) {
            throw new \Exception("Product qware_guid is required for order item ID: " . $orderItemId);
        }

        // 生成訂單編號 (YYYYMMDD + 廠商統編 + 交易編號)
        $orderNo = $this->generateOrderNumber($orderItemId);
        
        // 取得商品價格和數量
        $quantity = (int) ($requestQuantity ?: $orderItem->getQtyOrdered());
        $unitPrice = $this->getUnitPriceForRequest($product);
        $amount = $unitPrice * $quantity;

        $this->curlHeader = [
            "Content-Type" => self::CURL_HEADER_CONTENT_TYPE,
        ];

        $requestDataArray = [
            "AppId"                => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_APP_ID),
            "AccessKey"            => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_APP_KEY),
            "OrderNo"              => $orderNo,
            "Amount"               => (string) (int) $amount,
            "ExtraFee"             => "0",
            "OrderDate"            => date("Y-m-d H:i:s"),
            "ItemQty"              => 1, // 品項數固定為1
            "PaymentType"          => 7, // 信用卡
            "PaymentDate"          => date("Y-m-d H:i:s"),
            "CommodityGuid1"       => $qwareGuid,
            "CommodityQty1"        => $quantity,
            "CommodityPrice1"      => (string) (int) $unitPrice,
            "CommodityCommission1" => "0",
            "OnlineTicketNeed"     => "true",
            "PwNeed"               => "true"
        ];

        $response = $this->sendRequest(self::API_ROUTE_CREATE_ORDER, $requestDataArray);
        $statusCode = $response['status_code'];
        $responseBody = $response['body'];

        if ($statusCode !== 200) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"            => "Something went wrong while requesting Create Order.",
                "Order item ID"    => $orderItemId,
                "Request api path" => $this->apiPath,
                "Request header"   => $this->curlHeader,
                "Request data"     => $this->curlBody,
                "Response string"  => $responseBody,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            // 發送郵件通知
            try {
                $this->emailNotificationService->sendApiErrorNotification(
                    'requestApiGetMultiVouchers',
                    $orderItemId,  
                    $statusCode,
                    null,
                    'HTTP Error: ' . $responseBody
                );
            } catch (\Exception $e) {
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    'Title' => 'Failed to send API error email notification',
                    'OrderItemId' => $orderItemId,
                    'Error' => $e->getMessage()
                ]), self::LOG_FOLDER_NAME);
            }

            throw new \Exception(json_encode([
                "Message"         => "Something went wrong while requesting Qware Create Order API.",
                "Response string" => $responseBody,
            ]));
        }

        $responseArray = json_decode($responseBody, true);
        
        // 檢查 API 回應碼：200 成功，202 成功但需等待處理
        if (!isset($responseArray['Code']) || !in_array($responseArray['Code'], [200, 202])) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"            => "Qware Create Order API returned error code.",
                "Order item ID"    => $orderItemId,
                "Request data"     => $requestDataArray,
                "Response"         => $responseArray,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            $errorMessage = $responseArray['Message'] ?? 'Unknown error';
            $errorCode = $responseArray['Code'] ?? 'Unknown code';

            // 特殊處理 409 重複訂單錯誤
            if ($errorCode == 409) {
                try {
                    return $this->handleDuplicateOrder409($orderNo, $orderItemId);
                } catch (\Exception $recoveryException) {
                    // 409 恢復失敗，記錄詳細錯誤後繼續原流程
                    $this->hotaiCoreCommonHelper->writeLog(json_encode([
                        'Title' => '409 Recovery Failed',
                        'Order No' => $orderNo,
                        'Order Item ID' => $orderItemId,
                        'Error' => $recoveryException->getMessage(),
                        'Action' => 'Will proceed with original error handling'
                    ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);
                }
            }

            // 發送郵件通知
            try {
                $this->emailNotificationService->sendApiErrorNotification(
                    'requestApiGetMultiVouchers',
                    $orderItemId,
                    200, // HTTP status was 200 but API code was not 200/202
                    $errorCode,
                    $errorMessage
                );
            } catch (\Exception $e) {
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    'Title' => 'Failed to send API error email notification',
                    'OrderItemId' => $orderItemId,
                    'Error' => $e->getMessage()
                ]), self::LOG_FOLDER_NAME);
            }
            
            throw new \Exception("Qware API Error (Code: {$errorCode}): {$errorMessage}");
        }

        // 加入自定義訂單編號
        $responseArray['OrderNo'] = $orderNo;

        return $responseArray;
    }

    /**
     * 根據票券序號查詢票券詳細資訊 (包含 URL 和密碼)
     *
     * @param array $ticketSns 票券序號陣列，最多20筆
     * @return array
     */
    public function requestApiQueryTickets(array $ticketSns): array
    {
        if (empty($ticketSns)) {
            throw new \Exception("Ticket SNs array cannot be empty");
        }

        if (count($ticketSns) > 20) {
            throw new \Exception("Maximum 20 ticket SNs allowed per request");
        }

        $this->curlHeader = [
            "Content-Type" => self::CURL_HEADER_CONTENT_TYPE,
        ];

        $requestDataArray = [
            "AppId"     => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_APP_ID),
            "AccessKey" => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_APP_KEY),
            "TicketSns" => implode(',', $ticketSns)
        ];

        $response = $this->sendRequest(self::API_ROUTE_QUERY_TICKETS, $requestDataArray);
        $statusCode = $response['status_code'];
        $responseBody = $response['body'];

        if ($statusCode !== 200) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"            => "Something went wrong while requesting Query Tickets.",
                "Ticket SNs"       => $ticketSns,
                "Request api path" => $this->apiPath,
                "Request header"   => $this->curlHeader,
                "Request data"     => $this->curlBody,
                "Response string"  => $responseBody,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            throw new \Exception(json_encode([
                "Message"         => "Something went wrong while requesting Qware Query Tickets API.",
                "Response string" => $responseBody,
            ]));
        }

        $responseArray = json_decode($responseBody, true);
        
        // 檢查 API 回應碼：200 成功
        if (!isset($responseArray['Code']) || $responseArray['Code'] !== 200) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"            => "Qware Query Tickets API returned error code.",
                "Ticket SNs"       => $ticketSns,
                "Request data"     => $requestDataArray,
                "Response"         => $responseArray,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            $errorMessage = $responseArray['Message'] ?? 'Unknown error';
            $errorCode = $responseArray['Code'] ?? 'Unknown code';
            
            throw new \Exception("Qware Query Tickets API Error (Code: {$errorCode}): {$errorMessage}");
        }

        return $responseArray;
    }

    /**
     * 查詢訂單狀態並取得票券序號
     *
     * @param string $orderNo 訂單編號
     * @return array
     */
    public function requestApiQueryOrder(string $orderNo): array
    {
        $this->curlHeader = [
            "Content-Type" => self::CURL_HEADER_CONTENT_TYPE,
        ];

        $requestDataArray = [
            "AppId"     => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_APP_ID),
            "AccessKey" => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_APP_KEY),
            "OrderNo"   => $orderNo
        ];

        $response = $this->sendRequest(self::API_ROUTE_QUERY_ORDER, $requestDataArray);
        $statusCode = $response['status_code'];
        $responseBody = $response['body'];

        if ($statusCode !== 200) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"            => "Something went wrong while requesting Query Order.",
                "Order Number"     => $orderNo,
                "Request api path" => $this->apiPath,
                "Request header"   => $this->curlHeader,
                "Request data"     => $this->curlBody,
                "Response string"  => $responseBody,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            throw new \Exception(json_encode([
                "Message"         => "Something went wrong while requesting Qware Query Order API.",
                "Response string" => $responseBody,
            ]));
        }

        $responseArray = json_decode($responseBody, true);
        
        // 檢查 API 回應碼：200 完成，202 新訂單，208 取號完成
        if (!isset($responseArray['Code']) || !in_array($responseArray['Code'], [200, 202, 208])) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"            => "Qware Query Order API returned error code.",
                "Order Number"     => $orderNo,
                "Request data"     => $requestDataArray,
                "Response"         => $responseArray,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            $errorMessage = $responseArray['Message'] ?? 'Unknown error';
            $errorCode = $responseArray['Code'] ?? 'Unknown code';
            
            throw new \Exception("Qware Query Order API Error (Code: {$errorCode}): {$errorMessage}");
        }

        return $responseArray;
    }

    /**
     * 票券作廢(即時) - RefundImmediately API
     *
     * @param string $orderNo 訂單編號
     * @param array $ticketSns 票券序號陣列，最多5筆
     * @return array API回應資料
     * @throws \Exception
     */
    public function requestApiRefundImmediately(string $orderNo, array $ticketSns): array
    {
        if (empty($orderNo)) {
            throw new \Exception("Order number is required for refund request");
        }

        if (empty($ticketSns)) {
            throw new \Exception("Ticket SNs array cannot be empty");
        }

        if (count($ticketSns) > 5) {
            throw new \Exception("Maximum 5 ticket SNs allowed per refund request (Current: " . count($ticketSns) . ")");
        }

        $this->curlHeader = [
            "Content-Type" => self::CURL_HEADER_CONTENT_TYPE,
        ];

        $appId = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_APP_ID);
        $accessKey = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_APP_KEY);

        $requestDataArray = [
            "AppId"     => $appId,
            "AccessKey" => $accessKey,
            "TicketsSn" => implode(',', $ticketSns),
            "OrderNo"   => $orderNo
        ];

        // 記錄開始作廢的 log
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Title"       => "Starting Qware RefundImmediately request",
            "OrderNo"     => $orderNo,
            "TicketCount" => count($ticketSns),
            "TicketsSn"   => $ticketSns,
            "AppId"       => $appId,
            "AccessKey"   => $accessKey,
            "Timestamp"   => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

        $response = $this->sendRequest(self::API_ROUTE_REFUND_IMMEDIATELY, $requestDataArray);
        $statusCode = $response['status_code'];
        $responseBody = $response['body'];

        if ($statusCode !== 200) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"            => "HTTP error while requesting RefundImmediately API.",
                "OrderNo"          => $orderNo,
                "TicketSns"        => $ticketSns,
                "HTTP Status"      => $statusCode,
                "Request api path" => $this->apiPath,
                "Request header"   => $this->curlHeader,
                "Request data"     => $requestDataArray,
                "Response string"  => $responseBody,
            ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            throw new \Exception(json_encode([
                "Message"         => "HTTP error while requesting Qware RefundImmediately API.",
                "HTTP Status"     => $statusCode,
                "Response string" => $responseBody,
            ]));
        }

        $responseArray = json_decode($responseBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response from RefundImmediately API: " . json_last_error_msg());
        }

        // 檢查 API 回應碼
        if (!isset($responseArray['Code'])) {
            throw new \Exception("Invalid RefundImmediately API response format: missing Code field");
        }

        $responseCode = $responseArray['Code'];
        $responseMessage = $responseArray['Message'] ?? 'No message provided';

        // 有效的回應碼
        $validCodes = [200];
        
        if (!in_array($responseCode, $validCodes)) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"        => "Unexpected response code from RefundImmediately API",
                "OrderNo"      => $orderNo,
                "ResponseCode" => $responseCode,
                "Message"      => $responseMessage,
                "FullResponse" => $responseArray,
            ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);
        }

        // 記錄個別票券的作廢結果
        if (isset($responseArray['Data']) && is_array($responseArray['Data'])) {
            foreach ($responseArray['Data'] as $ticketResult) {
                $sn = $ticketResult['Sn'] ?? 'N/A';
                $success = $ticketResult['Success'] ?? false;
                $returnCode = $ticketResult['ReturnCode'] ?? 'N/A';
                $returnMessage = $ticketResult['ReturnMessage'] ?? 'N/A';
                
                $logLevel = $success ? 'INFO' : 'WARNING';
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Title"         => "Individual Ticket Refund Result",
                    "Level"         => $logLevel,
                    "OrderNo"       => $orderNo,
                    "TicketSn"      => $sn,
                    "Success"       => $success,
                    "ReturnCode"    => $returnCode,
                    "ReturnMessage" => $returnMessage,
                    "Timestamp"     => date('Y-m-d H:i:s')
                ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);
            }
        }

        return $responseArray;
    }

    // ==============================================
    // 內部使用 functions
    // ==============================================

    /**
     * 檢查請求票券的數量
     * 1. 不可小於0
     * 2. 不可超過安源票券的單筆請求上限
     *
     * @param integer $requestQuantity
     * @return void
     */
    protected function checkRequestQuantityForGetMultiVouchers(int $requestQuantity): void
    {
        if ($requestQuantity < 0) {
            throw new \Exception(__("Request quantity for Qware ticket must greater than 0."));
        }

        if ($requestQuantity > self::QUANTITY_LIMIT) {
            throw new \Exception(__("Request quantity for Qware ticket must lower than " . self::QUANTITY_LIMIT . "."));
        }
    }

    /**
     * 生成安源訂單編號
     * 格式：YYYYMMDD(8) + 廠商統編(8) + 廠商自行定義不重複的交易編號(6)
     *
     * @param integer $orderItemId
     * @return string
     */
    protected function generateOrderNumber(int $orderItemId): string
    {
        $date = date('Ymd'); // 8位日期
        $companyId = self::COMPANY_ID; // 8位廠商統編
        
        // 生成6位不重複交易編號 (使用 order_item_id 確保唯一性)
        $transactionId = str_pad($orderItemId % 999999, 6, '0', STR_PAD_LEFT);
        
        return $date . $companyId . $transactionId;
    }

    /**
     * 根據產品回傳API請求要傳遞的UnitPrice欄位值
     * (成本價優先, 沒有的話只好傳一般售價)
     *
     * @param Product $product
     * @return float
     */
    public function getUnitPriceForRequest(Product $product): float
    {
        return empty($product->getCost()) ? $product->getPrice() : $product->getCost();
    }

    /**
     * 送出curl請求
     *
     * @param string $apiRoute
     * @param array $requestDataArray
     * @return array
     */
    private function sendRequest(string $apiRoute, array $requestDataArray): array
    {
        // Reset prarmeters
        $this->apiPath           = null;
        $this->requestDataArray  = null;
        $this->requestDataString = null;
        $this->curlBody          = null;

        // Set api path.
        $domain        = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_API_DOMAIN);
        $domain        = rtrim($domain, "/");
        $this->apiPath = "{$domain}/{$apiRoute}";

        // Set request data(before encrypt).
        $this->requestDataArray  = $requestDataArray;
        $this->requestDataString = json_encode($this->requestDataArray, JSON_UNESCAPED_SLASHES);
        // ------------------------------------------------

        // Set curl body
        $this->curlBody = http_build_query($this->requestDataArray);
        // ------------------------------------------------

        $this->curl->setHeaders($this->curlHeader);
        $this->curl->setTimeout(self::CURL_TIMEOUT_SECONDS);

        $exception = null;
        $requestStartTime = microtime(true);
        
        // 若curl逾時則以相同參數重送直到重試次數上限
        for ($retryCount = 1; $retryCount <= self::CURL_TIMEOUT_RETRY_LIMIT; $retryCount++) {
            try {
                $this->curl->post($this->apiPath, $this->curlBody);

                break;
            } catch (\Exception $e) {
                $exception = $e;
                if ($this->isTimeoutException() && !$this->isCurlRetryHitLimit($retryCount)) {
                    // 記錄重試 log
                    $this->hotaiCoreCommonHelper->writeLog(json_encode([
                        'Title' => 'Qware API Request Timeout - Retrying',
                        'URL' => $this->apiPath,
                        'Retry Count' => $retryCount,
                        'Max Retries' => self::CURL_TIMEOUT_RETRY_LIMIT,
                        'Error' => $e->getMessage(),
                        'Timestamp' => date('Y-m-d H:i:s')
                    ], JSON_UNESCAPED_SLASHES), 'Qware/Api');
                    continue;
                }

                throw $e;
            }
        }

        $response = [
            'status_code' => $this->curl->getStatus(),
            'body' => $this->curl->getBody()
        ];
        
        $requestEndTime = microtime(true);
        $executionTime = round(($requestEndTime - $requestStartTime) * 1000, 2); // ms

        // 記錄完整的 API 通訊 log (請求 + 回應)
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            'Title' => 'Qware API Communication',
            'Request' => [
                'URL' => $this->apiPath,
                'Method' => 'POST',
                'Headers' => $this->curlHeader,
                'Data' => $this->requestDataArray,
                'Body' => $this->curlBody
            ],
            'Response' => [
                'Status Code' => $response['status_code'],
                'Body' => $response['body'],
                'Data' => json_decode($response['body'], true)
            ],
            'Execution Time (ms)' => $executionTime,
            'Retry Count' => isset($retryCount) ? $retryCount - 1 : 0,
            'Had Exception' => $exception ? $exception->getMessage() : null,
            'Timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_SLASHES), 'Qware/Api');

        return $response;
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
     * 處理 409 重複訂單錯誤
     * 立即查詢訂單狀態，如果訂單存在且完成則返回票券資料
     *
     * @param string $orderNo 訂單編號
     * @param int $orderItemId 訂單項目ID
     * @return array 標準的 Create API 格式 + 額外的票券詳細資訊
     * @throws \Exception
     */
    protected function handleDuplicateOrder409(string $orderNo, int $orderItemId): array
    {
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            'Title' => 'Handling 409 Duplicate Order',
            'Order No' => $orderNo,
            'Order Item ID' => $orderItemId,
            'Action' => 'Querying order status from Qware'
        ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

        // Step 1: 查詢訂單 (重用現有方法)
        $queryResponse = $this->requestApiQueryOrder($orderNo);

        // Step 2: 驗證查詢結果
        $queryCode = $queryResponse['Code'] ?? null;

        if ($queryCode == 200) {
            // 訂單已完成，可以立即處理
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                'Title' => '409 Resolved - Order Completed',
                'Order No' => $orderNo,
                'Query Code' => 200,
                'Tickets Count' => count($queryResponse['Data'] ?? []),
                'Action' => 'Converting to standard format'
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            // Step 3: 轉換為標準格式
            return $this->convertQueryToCreateFormat($queryResponse, $orderNo);

        } else {
            // 查詢失敗或訂單仍在處理中
            throw new \Exception(
                "409 duplicate order but query returned code: {$queryCode}. " .
                "Order may still be processing or does not exist in orders table."
            );
        }
    }

    /**
     * 將 Query API 格式轉換為 Create API 格式
     *
     * Query 格式: {"Data": [{"Sn":"xxx", "Ticket":{...}}], "Code":200}
     * Create 格式: {"Data": {"guid": ["SN1", "SN2"]}, "Code":200, "OrderNo":"xxx"}
     *
     * @param array $queryResponse Query API 原始回應
     * @param string $orderNo 訂單編號
     * @return array Create API 相容格式
     */
    protected function convertQueryToCreateFormat(array $queryResponse, string $orderNo): array
    {
        $ticketsData = $queryResponse['Data'] ?? [];
        $guidToSnsMap = [];

        // 解析 Query API 回應格式
        foreach ($ticketsData as $item) {
            $ticket = $item['Ticket'] ?? [];
            $sn = $ticket['Sn'] ?? '';
            $guid = $ticket['CommodityGuid'] ?? '';

            if (!empty($sn) && !empty($guid)) {
                // 建立 GUID -> SNs 映射 (Create API 格式)
                if (!isset($guidToSnsMap[$guid])) {
                    $guidToSnsMap[$guid] = [];
                }
                $guidToSnsMap[$guid][] = $sn;
            }
        }

        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            'Title' => '409 Recovery - Convert Query to Create Format',
            'Order No' => $orderNo,
            'GUIDs Count' => count($guidToSnsMap),
            'Note' => 'Will call updateTicketDetailsFromApi to get URL/Password'
        ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

        // 返回 Create API 相容格式
        return [
            'Data' => $guidToSnsMap,
            'Code' => $queryResponse['Code'],
            'Message' => $queryResponse['Message'] ?? null,
            'OrderNo' => $orderNo,
            '_source' => 'query_api_409_recovery'
        ];
    }
}