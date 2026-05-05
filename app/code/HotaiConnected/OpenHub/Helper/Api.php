<?php

namespace HotaiConnected\OpenHub\Helper;

use HotaiConnected\OpenHub\Helper\Common as CommonHelper;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Helper\Curl;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Sales\Model\Order\Item;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Psr\Log\LoggerInterface;

class Api
{
    const LOG_FOLDER_NAME = 'OpenHub/Api';

    const API_ROUTE_CREATE_ORDER = "carwash/api/order/partner-order";
    const API_ROUTE_RETURN = "carwash/api/order/return";

    const CURL_HEADER_CONTENT_TYPE = "application/json";
    const CURL_TIMEOUT_ERROR_CODE  = 28;
    const CURL_TIMEOUT_SECONDS     = 30;
    const CURL_TIMEOUT_RETRY_LIMIT = 3;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var OrderItemRepositoryInterface */
    protected $orderItemRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var Curl */
    protected $curl;

    /** @var CustomerCollectionFactory */
    protected $customerCollectionFactory;

    /** @var LoggerInterface */
    protected $logger;

    protected $apiPath;
    protected $curlHeader;
    protected $curlBody;
    protected $requestDataArray;
    protected $requestDataString;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository,
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        CommonHelper $commonHelper,
        Curl $curl,
        CustomerCollectionFactory $customerCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->scopeConfig           = $scopeConfig;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->productRepository     = $productRepository;
        $this->customerRepository    = $customerRepository;
        $this->orderRepository       = $orderRepository;
        $this->orderItemRepository   = $orderItemRepository;
        $this->commonHelper          = $commonHelper;
        $this->curl                  = $curl;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->logger = $logger;

        $this->requestDataArray  = [];
        $this->requestDataString = "";
    }

    /**
     * 根據orderItemId向OpenHub API請求票券資料 (建立訂單)
     *
     * @param integer $orderItemId
     * @return array
     */
    public function requestApiCreateOrder(int $orderItemId): array
    {
        /** @var Item $orderItem */
        $orderItem = $this->orderItemRepository->get($orderItemId);
        /** @var Product $product */
        $product = $this->productRepository->getById($orderItem->getProductId());
        
        // 使用 Sales Order Item ID 作為交易編號
        $transactionNo = (string) $orderItemId;
        
        // 取得商品數量
        $quantity = (int) $orderItem->getQtyOrdered();
        
        // 取得商品的 openhub_product_id
        $openHubProductId = $product->getData(CommonHelper::ATTRIBUTE_CODE_OPENHUB_PRODUCT_ID);
        if (empty($openHubProductId)) {
            throw new \Exception("Product openhub_product_id is required for order item ID: " . $orderItemId);
        }
        
        // 取得用戶ID
        $order = $orderItem->getOrder();
        $customerId = $order->getCustomerId();

        $isGiftOrder = (int)$order->getData('is_gift_order');
        $isGiftConfirmed = (int)$order->getData('is_gift_confirmed');

        if ($isGiftOrder && $isGiftConfirmed) {
            // 禮物訂單：使用收禮人的 OneID
            $recipientTelephone = $order->getData('recipient_telephone');
            if (!$recipientTelephone) {
                $this->logger->error("[OpenHub] Gift order missing recipient_telephone error", [
                    'order_item_id' => $orderItemId,
                    'order_id' => $order->getId(),
                ]);
                throw new \Exception("Gift order missing recipient_telephone, order item ID: " . $orderItemId);
            }

            $recipientOneId = $this->getOneIdByPhone($recipientTelephone);
            if (!$recipientOneId) {
                $this->logger->error("[OpenHub] Cannot resolve recipient OneID for gift order error", [
                    'order_item_id' => $orderItemId,
                    'order_id' => $order->getId(),
                    'recipient_phone' => $recipientTelephone,
                ]);
                throw new \Exception(
                    "Cannot resolve recipient OneID for gift order, "
                    . "recipient phone: {$recipientTelephone}, order item ID: " . $orderItemId
                );
            }

            $oneIds = [$recipientOneId];

        } else {
            // 一般訂單：使用購買人的 OneID
            $oneIds = [$this->getOneIdByCustomerId($customerId)];
        }

        $this->curlHeader = [
            "Content-Type" => self::CURL_HEADER_CONTENT_TYPE,
            "x-api-key" => $this->commonHelper->getApiKey()
        ];

        $requestDataArray = [
            "remark" => "Magento order for customer " . $orderItem->getOrder()->getCustomerId(),
            "appId" => "mytoyota",
            "transactionNo" => $transactionNo,
            "items" => [
                [
                    "productId" => (int) $openHubProductId,
                    "quantity" => $quantity,
                    "userId" => $oneIds
                ]
            ]
        ];

        $response = $this->sendRequest(self::API_ROUTE_CREATE_ORDER, $requestDataArray);
        $statusCode = $response['status_code'];
        $responseBody = $response['body'];

        if ($statusCode !== 200) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"            => "Something went wrong while requesting OpenHub Create Order.",
                "Order item ID"    => $orderItemId,
                "Request api path" => $this->apiPath,
                "Request header"   => $this->curlHeader,
                "Request data"     => $this->curlBody,
                "Response string"  => $responseBody,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            throw new \Exception(json_encode([
                "Message"         => "Something went wrong while requesting OpenHub Create Order API.",
                "Response string" => $responseBody,
            ]));
        }

        $responseArray = json_decode($responseBody, true);
        
        // 檢查 API 回應：errorCode 為 null 表示成功
        if (isset($responseArray['errorCode']) && $responseArray['errorCode'] !== null) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"            => "OpenHub Create Order API returned error code.",
                "Order item ID"    => $orderItemId,
                "Request data"     => $requestDataArray,
                "Response"         => $responseArray,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            $errorMessage = $responseArray['message'] ?? 'Unknown error';
            $errorCode = $responseArray['errorCode'] ?? 'Unknown code';
            
            throw new \Exception("OpenHub API Error (Code: {$errorCode}): {$errorMessage}");
        }

        // API 回應已包含 transactionNo，不需要覆蓋

        return $responseArray;
    }

    /**
     * 根據訂單項目向OpenHub API請求退貨
     *
     * @param string $externalTransactionNo
     * @param int $openHubProductId
     * @param int $quantity
     * @return array
     */
    public function requestApiReturn(string $externalTransactionNo, int $openHubProductId, int $quantity): array
    {
        if (empty($externalTransactionNo)) {
            throw new \Exception("External transaction number is required for return request");
        }

        if (empty($openHubProductId)) {
            throw new \Exception("OpenHub product ID is required for return request");
        }

        if ($quantity <= 0) {
            throw new \Exception("Return quantity must be greater than 0");
        }

        // 記錄開始退貨的 log
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Title"              => "Starting OpenHub Return request",
            "TransactionNo"      => $externalTransactionNo,
            "OpenHub Product ID" => $openHubProductId,
            "Quantity"           => $quantity,
            "Timestamp"          => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

        $this->curlHeader = [
            "Content-Type" => self::CURL_HEADER_CONTENT_TYPE,
            "x-api-key" => $this->commonHelper->getApiKey()
        ];

        // 根據 API 文件格式建立請求資料
        $requestDataArray = [
            "transactionNo" => $externalTransactionNo,
            "returnItem" => [
                [
                    "productId" => $openHubProductId,
                    "quantity" => $quantity
                ]
            ]
        ];

        $response = $this->sendRequest(self::API_ROUTE_RETURN, $requestDataArray);
        $statusCode = $response['status_code'];
        $responseBody = $response['body'];

        if ($statusCode !== 200) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"              => "HTTP error while requesting OpenHub Return API.",
                "TransactionNo"      => $externalTransactionNo,
                "OpenHub Product ID" => $openHubProductId,
                "Quantity"           => $quantity,
                "HTTP Status"        => $statusCode,
                "Request api path"   => $this->apiPath,
                "Request header"     => $this->curlHeader,
                "Request data"       => $requestDataArray,
                "Response string"    => $responseBody,
            ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            throw new \Exception(json_encode([
                "Message"         => "HTTP error while requesting OpenHub Return API.",
                "HTTP Status"     => $statusCode,
                "Response string" => $responseBody,
            ]));
        }

        $responseArray = json_decode($responseBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response from OpenHub Return API: " . json_last_error_msg());
        }

        // 檢查 API 回應：errorCode 為 null 表示成功
        if (isset($responseArray['errorCode']) && $responseArray['errorCode'] !== null) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"              => "OpenHub Return API returned error code.",
                "TransactionNo"      => $externalTransactionNo,
                "OpenHub Product ID" => $openHubProductId,
                "Quantity"           => $quantity,
                "Request data"       => $requestDataArray,
                "Response"           => $responseArray,
            ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            $errorMessage = $responseArray['message'] ?? 'Unknown error';
            $errorCode = $responseArray['errorCode'] ?? 'Unknown code';
            
            throw new \Exception("OpenHub Return API Error (Code: {$errorCode}): {$errorMessage}");
        }

        // 記錄成功退貨的 log
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "Title"              => "OpenHub Return API success",
            "TransactionNo"      => $externalTransactionNo,
            "OpenHub Product ID" => $openHubProductId,
            "Quantity"           => $quantity,
            "Response"           => $responseArray,
            "Timestamp"          => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

        return $responseArray;
    }



    /**
     * 根據手機號碼取得 oneId (member_seq)
     *
     * @param string $phone
     * @return string|null
     */
    private function getOneIdByPhone(string $phone): ?string
    {
        try {
            $collection = $this->customerCollectionFactory->create()
                ->addAttributeToFilter('phone_number', $phone)
                ->addAttributeToSelect('member_seq');
            $collection->getSelect()->order('entity_id desc')->limit(1);

            $customer = $collection->getFirstItem();
            if ($customer && $customer->getId() && $customer->getData('member_seq')) {
                return $customer->getData('member_seq');
            }
        } catch (\Exception $e) {
            // 如果查詢失敗，返回 null
        }

        return null;
    }

    /**
     * 取得客戶的 oneId (member_seq)
     *
     * @param int $customerId
     * @return string|null
     */
    private function getOneIdByCustomerId(int $customerId): ?string
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            
            if ($customer->getCustomAttribute('member_seq')) {
                return $customer->getCustomAttribute('member_seq')->getValue();
            }
        } catch (\Exception $e) {
            // 如果無法取得 member_seq，返回 null
        }
        
        return null;
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
        // Reset parameters
        $this->apiPath           = null;
        $this->requestDataArray  = null;
        $this->requestDataString = null;
        $this->curlBody          = null;

        // Set api path
        $domain        = $this->commonHelper->getApiDomain();
        $domain        = rtrim($domain, "/");
        $this->apiPath = "{$domain}/{$apiRoute}";
        
        // Debug log
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            "API Debug" => [
                "Domain" => $domain,
                "API Route" => $apiRoute,
                "Final API Path" => $this->apiPath
            ]
        ]), self::LOG_FOLDER_NAME);

        // Set request data
        $this->requestDataArray  = $requestDataArray;
        $this->requestDataString = json_encode($this->requestDataArray, JSON_UNESCAPED_SLASHES);

        // Set curl body
        $this->curlBody = $this->requestDataString;

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
                        'Title' => 'OpenHub API Request Timeout - Retrying',
                        'URL' => $this->apiPath,
                        'Retry Count' => $retryCount,
                        'Max Retries' => self::CURL_TIMEOUT_RETRY_LIMIT,
                        'Error' => $e->getMessage(),
                        'Timestamp' => date('Y-m-d H:i:s')
                    ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);
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

        // 記錄完整的 API 通訊 log
        $this->hotaiCoreCommonHelper->writeLog(json_encode([
            'Title' => 'OpenHub API Communication',
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
        ], JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

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
}