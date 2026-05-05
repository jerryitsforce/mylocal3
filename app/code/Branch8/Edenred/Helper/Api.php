<?php

namespace Branch8\Edenred\Helper;

use Branch8\Edenred\Helper\Common as CommonHelper;
use Branch8\Edenred\Model\Config\Source\LogOption as EdenredLogOption;
use Branch8\Edenred\Model\EdenredTicketRecord;
use Branch8\Edenred\Model\EdenredTicketRecordRepository;
use Branch8\HotaiCore\Helper\Curl;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Catalog\Model\Product;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;

class Api
{
    const LOG_FOLDER_NAME = 'Edenred/Api';
    const LOG_OPTION_VALUE = EdenredLogOption::LOG_OPTION_VALUE_EDENRED_API;

    const API_ROUTE_GET_MULTI_VOUCHERS    = "GetMultiVouchers.svc/GetMultiVouchersData";
    const API_ROUTE_CANCEL_MULTI_VOUCHERS = "CancelMultiVouchers.svc/CancelMultiVouchersData";

    const QUANTITY_LIMIT = 10;

    const ENCRYPT_METHOD = "des-ede3-cbc";

    const INVOICE_CARRIER_TYPE_MEMBER               = "1";
    const INVOICE_CARRIER_TYPE_MOBILE_BARCODE       = "2";
    const INVOICE_CARRIER_TYPE_PERSONAL_CERTIFICATE = "3";
    const INVOICE_CARRIER_TYPE_DONATION             = "4";
    const INVOICE_CARRIER_TYPE_BUSINESS             = "5";
    const INVOICE_CARRIER_TYPE_CONSUMER_CODE        = "6";

    const REQUEST_USER_TYPE_FOR_CANCEL = "PURCHASER";

    const API_RESPONSE_CODE_SUCCESS = "RC00";

    const CURL_HEADER_CONTENT_TYPE = "application/json; charset=UTF-8";

    const CURL_TIMEOUT_ERROR_CODE  = 28;
    const CURL_TIMEOUT_SECONDS     = 30;
    const CURL_TIMEOUT_RETRY_LIMIT = 3;

    const CLIENT_ORDER_NUMBER_MAX_LENGTH = 20;
    const CLIENT_ORDER_NUMBER_PREFIX_DEFAULT = "ED_";

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var OrderItemRepositoryInterface */
    protected $orderItemRepository;

    /** @var EdenredTicketRecordRepository */
    protected $edenredTicketRecordRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var Curl */
    protected $curl;

    protected $apiPath;
    protected $curlHeader;
    protected $curlBody;
    protected $requestDataArray;
    protected $requestDataString;
    protected $sslKey;
    protected $sslIv;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ProductRepositoryInterface $productRepository,
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        EdenredTicketRecordRepository $edenredTicketRecordRepository,
        CommonHelper $commonHelper,
        Curl $curl
    ) {
        $this->scopeConfig                   = $scopeConfig;
        $this->productRepository             = $productRepository;
        $this->orderRepository               = $orderRepository;
        $this->orderItemRepository           = $orderItemRepository;
        $this->edenredTicketRecordRepository = $edenredTicketRecordRepository;
        $this->commonHelper                  = $commonHelper;
        $this->curl                          = $curl;

        $this->requestDataArray  = [];
        $this->requestDataString = "";
    }

    /**
     * 根據orderItemId向宜睿票券API請求票券資料
     * (用在訂單確定付款後)
     *
     * @param integer $orderItemId
     * @param integer $requestQuantity
     * @return array
     */
    public function requestApiGetMultiVouchers(int $orderItemId, int $requestQuantity = 0): array
    {
        $this->checkRequestQuantityForGetMultiVouchers($requestQuantity);

        $this->curlHeader = null;

        $consumerCode = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_CONSUMER_CODE);
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $orderItem         = $this->orderItemRepository->get($orderItemId);
        $clientOrderNumber = $this->generateClientOrderNumber($orderItemId);
        /** @var Product $product */
        $product             = $this->productRepository->getById($orderItem->getProductId());
        $edenredOrderNumber  = $product->getData(CommonHelper::ATTRIBUTE_CODE_EDENRED_ORDER_NUMBER);
        $edenredProductCode  = $product->getData(CommonHelper::ATTRIBUTE_CODE_EDENRED_PRODUCT_CODE);
        $edenredMerchantCode = $product->getData(CommonHelper::ATTRIBUTE_CODE_EDENRED_MERCHANT_CODE);

        $this->curlHeader = [
            "Content-Type"      => self::CURL_HEADER_CONTENT_TYPE,
            "APIVersion"        => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_API_VERSION),
            "ConsumerCode"      => $consumerCode,
            "WebClientSecurity" => $this->commonHelper->encryptString("{$consumerCode}{$clientOrderNumber}{$edenredProductCode}{$edenredMerchantCode}"),
        ];

        $requestDataArray = [
            "OrderNumber"        => $edenredOrderNumber,
            "ClientOrderNumber"  => $clientOrderNumber,
            "ProductCode"        => $edenredProductCode,
            "MerchantCode"       => $edenredMerchantCode,
            "OrderCnt"           => (int) ($requestQuantity ?: $orderItem->getQtyOrdered()),
            "Amount"             => $orderItem->getRowTotalInclTax(),
            "UnitPrice"          => $this->getUnitPriceForRequest($product),
            "InvoiceCarrierType" => self::INVOICE_CARRIER_TYPE_MEMBER,
        ];

        $responseString = $this->sendRequest(self::API_ROUTE_GET_MULTI_VOUCHERS, $requestDataArray);
        $responseArray  = json_decode($responseString, true);

        if (!$this->validateApiResponse($responseString)) {
            $this->commonHelper->writeLogIfEnabled(json_encode([
                "Title"            => "Something went wrong while requesting GetMultiVouchers.",
                "Order item ID"    => $orderItemId,
                "Request api path" => $this->apiPath,
                "Request header"   => $this->curlHeader,
                "Request data"     => $this->curlBody,
                "Response string"  => $responseString,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

            throw new \Exception(json_encode([
                "Message"         => "Something went wrong while requesting Edenred API.",
                "Response string" => $responseString,
            ]));
        }

        return $responseArray;
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
     * 根據orderItemId向宜睿票券API請求取消票券
     * (用在訂單被取消的狀況)
     * @param integer $orderItemId
     * @return array
     */
    public function requestApiCancelMultiVouchers(int $orderItemId): array
    {
        $this->curlHeader = null;

        $consumerCode = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_CONSUMER_CODE);
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $orderItem = $this->orderItemRepository->get($orderItemId);

        $collection = $this->edenredTicketRecordRepository->getRecordsByOrderItemIdAndStatus($orderItemId, EdenredTicketRecord::STATUS_IMPORTED);
        /** @var \Branch8\Edenred\Model\EdenredTicketRecord[] $recordArray */
        $recordArray = $collection->getItems();

        if (empty($recordArray)) {
            throw new \Exception(__("No records in Edenred ticket record table for order item ID: " . $orderItemId));
        }

        $edenredProductCode  = $recordArray[0]->getEdenredProductCode();
        $edenredMerchantCode = $recordArray[0]->getEdenredMerchantCode();
        $clientOrderNumber   = $recordArray[0]->getEdenredClientOrderNumber();
        $reqDate             = date("Y-m-d H:i:s");

        $vouchersArray = [];
        foreach ($recordArray as $record) {
            $vouchersArray[] = $record->getEdenredVoucherNo();
        }
        $vouchersString = implode(",", $vouchersArray);

        $this->curlHeader = [
            "Content-Type"      => self::CURL_HEADER_CONTENT_TYPE,
            "ConsumerCode"      => $consumerCode,
            "WebClientSecurity" => $this->commonHelper->encryptString("{$consumerCode}{$clientOrderNumber}{$edenredProductCode}{$edenredMerchantCode}"),
        ];

        $requestDataArray = [
            "MerchantCode"      => $edenredMerchantCode,
            "ProductCode"       => $edenredProductCode,
            "ClientOrderNumber" => $clientOrderNumber,
            "OrderCnt"          => (int) $orderItem->getQtyOrdered(),
            "RequestUserType"   => self::REQUEST_USER_TYPE_FOR_CANCEL,
            "Vouchers"          => $this->commonHelper->encryptString($vouchersString),
            "ReqDate"           => $reqDate,
        ];

        $responseString = $this->sendRequest(self::API_ROUTE_CANCEL_MULTI_VOUCHERS, $requestDataArray);
        $responseArray  = json_decode($responseString, true);

        if (!$this->validateApiResponse($responseString)) {
            $this->commonHelper->writeLogIfEnabled(json_encode([
                "Title"                   => "Something went wrong while requesting CancelMultiVouchers.",
                "Order item ID"           => $orderItemId,
                "Vouchers before encrypt" => $vouchersString,
                "Request api path"        => $this->apiPath,
                "Request header"          => $this->curlHeader,
                "Request data"            => $this->curlBody,
                "Response string"         => $responseString,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

            throw new \Exception(json_encode([
                "Message"         => "Something went wrong while requesting Edenred API.",
                "Response string" => $responseString,
            ]));
        }

        return $responseArray;
    }

    /**
     * 自行傳遞VoucherNo陣列向宜睿票券API請求取消票券
     * (根據請求資料看來, 傳遞的VoucherNo陣列必須屬於同一個orderItem)
     * example: ["voucherNo1", "voucherNo2"]
     * @param int $orderItemId
     * @return array
     */
    public function requestApiCancelMultiVouchersWithCustomVoucherNoArray(int $orderItemId, array $voucherNoArray): array
    {
        $this->curlHeader = null;

        $consumerCode = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::CONFIG_CODE_CONSUMER_CODE);
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $orderItem = $this->orderItemRepository->get($orderItemId);

        $collection = $this->edenredTicketRecordRepository->getRecordsByOrderItemIdAndStatus($orderItemId, TicketStatus::STATUS_UNUSED);
        /** @var \Branch8\Edenred\Model\EdenredTicketRecord[] $recordArray */
        $recordArray = $collection->getItems();

        if (empty($recordArray)) {
            throw new \Exception(__("No records in Edenred ticket record table for order item ID: " . $orderItemId));
        }

        $edenredProductCode  = $recordArray[0]->getEdenredProductCode();
        $edenredMerchantCode = $recordArray[0]->getEdenredMerchantCode();
        $clientOrderNumber   = $recordArray[0]->getEdenredClientOrderNumber();
        $reqDate             = date("Y-m-d H:i:s");

        $orderItemCheckerArray = [];
        foreach ($voucherNoArray as $voucherNo) {
            $ticketRecord = $this->edenredTicketRecordRepository->getRecordByVoucherNo($voucherNo);

            if (empty($ticketRecord)) {
                throw new \Exception(__("No record in Edenred ticket record table for voucherNo: " . $voucherNo));
            }

            $orderItemCheckerArray[$ticketRecord->getSalesOrderItemId()] = $ticketRecord->getSalesOrderItemId();
        }

        if (count($orderItemCheckerArray) > 1) {
            throw new \Exception(__("Input voucherNo belong to more than one order item: " . json_encode($voucherNoArray)));
        }

        $vouchersString = implode(",", $voucherNoArray);

        $this->curlHeader = [
            "Content-Type"      => self::CURL_HEADER_CONTENT_TYPE,
            "ConsumerCode"      => $consumerCode,
            "WebClientSecurity" => $this->commonHelper->encryptString("{$consumerCode}{$clientOrderNumber}{$edenredProductCode}{$edenredMerchantCode}"),
        ];

        $requestDataArray = [
            "MerchantCode"      => $edenredMerchantCode,
            "ProductCode"       => $edenredProductCode,
            "ClientOrderNumber" => $clientOrderNumber,
            "OrderCnt"          => (int) $orderItem->getQtyOrdered(),
            "RequestUserType"   => self::REQUEST_USER_TYPE_FOR_CANCEL,
            "Vouchers"          => $this->commonHelper->encryptString($vouchersString),
            "ReqDate"           => $reqDate,
        ];

        $responseString = $this->sendRequest(self::API_ROUTE_CANCEL_MULTI_VOUCHERS, $requestDataArray);
        $responseArray  = json_decode($responseString, true);

        if (!$this->validateApiResponse($responseString)) {
            $this->commonHelper->writeLogIfEnabled(json_encode([
                "Title"                   => "Something went wrong while requesting CancelMultiVouchersWithCustomVoucherNoArray.",
                "Order item ID"           => $orderItemId,
                "Vouchers before encrypt" => $vouchersString,
                "Request api path"        => $this->apiPath,
                "Request header"          => $this->curlHeader,
                "Request data"            => $this->curlBody,
                "Response string"         => $responseString,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME, self::LOG_OPTION_VALUE);

            throw new \Exception(json_encode([
                "Message"         => "Something went wrong while requesting Edenred API.",
                "Response string" => $responseString,
            ]));
        }

        return $responseArray;
    }

    /**
     * 檢查請求票券的數量
     * 1. 不可小於0
     * 2. 不可超過宜睿票券的單筆請求上限(self::QUANTITY_LIMIT)
     *
     * @param integer $requestQuantity
     * @return void
     */
    protected function checkRequestQuantityForGetMultiVouchers(int $requestQuantity): void
    {
        if ($requestQuantity < 0) {
            throw new \Exception(__("Request quantity for Edenred ticket must greater than 0."));
        }

        if ($requestQuantity > self::QUANTITY_LIMIT) {
            throw new \Exception(__("Request quantity for Edenred ticket must lower than " . self::QUANTITY_LIMIT . "."));
        }
    }

    /**
     * 從GetMultiVouchers的API response中取得票券資料array
     *
     * @param array $response
     * @return array
     */
    public function getVoucherDataFromGetMultiVouchersResponse(array $response): array
    {
        return $response["Vouchers"];
    }

    protected function generateClientOrderNumber(int $orderItemId): string
    {
        $prefix = $this->scopeConfig->getValue(CommonHelper::CONFIG_PATH_CLIENT_ORDER_NUMBER_PREFIX) ?? self::CLIENT_ORDER_NUMBER_PREFIX_DEFAULT;
        $prefixLength = mb_strlen($prefix);
        $maxOrderItemIdLength = self::CLIENT_ORDER_NUMBER_MAX_LENGTH - $prefixLength;
        $paddedOrderItemId = str_pad((string)$orderItemId, $maxOrderItemIdLength, "0", STR_PAD_LEFT);

        $clientOrderNumber = "{$prefix}{$paddedOrderItemId}";

        return mb_substr($clientOrderNumber, 0, self::CLIENT_ORDER_NUMBER_MAX_LENGTH);
    }

    protected function getOrderItemSequenceInOrder(int $orderItemId): int
    {
        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $orderItem = $this->orderItemRepository->get($orderItemId);
        $order     = $orderItem->getOrder();
        $sequence  = 1;

        foreach ($order->getAllVisibleItems() as $itemForCheck) {
            if ($itemForCheck->getId() == $orderItem->getId()) {
                return $sequence;
            }

            $sequence += 1;
        }

        return $sequence;
    }

    /**
     * 獲取 $this->requestDataArray 參數值
     * 主要用於查看最近一筆加密前API請求資料(array形式)
     *
     * @return array
     */
    public function getRequestDataArray(): array
    {
        return $this->requestDataArray;
    }

    /**
     * 獲取 $this->requestDataString 參數值
     * 主要用於查看最近一筆加密前API請求資料(string形式)
     *
     * @return string
     */
    public function getRequestDataString(): string
    {
        return $this->requestDataString;
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
        $this->curlBody = json_encode($this->requestDataArray, JSON_UNESCAPED_SLASHES);
        // ------------------------------------------------

        $this->curl->setHeaders($this->curlHeader);
        $this->curl->setTimeout(self::CURL_TIMEOUT_SECONDS);

        // 若curl逾時則以相同參數重送直到重試次數上限
        for ($retryCount = 1; $retryCount <= self::CURL_TIMEOUT_RETRY_LIMIT; $retryCount++) {
            try {
                $this->curl->post($this->apiPath, $this->curlBody);

                break;
            } catch (\Exception $e) {
                if ($this->isTimeoutException() && !$this->isCurlRetryHitLimit($retryCount)) {
                    continue;
                }

                throw $e;
            }
        }

        return $this->curl->getBody();
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
     * 判斷API回傳的狀態碼是否成功
     * (returnCode是否等於0000)
     *
     * @param string $decryptResponseString
     * @return boolean
     */
    private function validateApiResponse(string $decryptResponseString): bool
    {
        $decryptDataAry = json_decode($decryptResponseString, true);

        if (!isset($decryptDataAry["returnCode"])) {
            return false;
        }

        if ($decryptDataAry["returnCode"] != self::API_RESPONSE_CODE_SUCCESS) {
            return false;
        }

        return true;
    }
}
