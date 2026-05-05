<?php

namespace Branch8\Yoxi\Helper;

use Branch8\Yoxi\Helper\Common as CommonHelper;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Helper\Curl;

class Api
{
    const LOG_FOLDER_NAME = 'Yoxi/Api';

    const API_ROUTE_GET_COUPON_LIST   = "discount/coupon/list";
    const API_ROUTE_INVALIDATE_COUPON = "discount/coupon/invalidate";

    const ENCRYPT_METHOD = "AES-256-CBC";

    const REQUEST_USER_TYPE_FOR_CANCEL = "PURCHASER";

    const API_RESPONSE_CODE_SUCCESS = true;

    const CURL_HEADER_CONTENT_TYPE = "application/json; charset=UTF-8";

    const CURL_TIMEOUT_ERROR_CODE  = 28;
    const CURL_TIMEOUT_SECONDS     = 30;
    const CURL_TIMEOUT_RETRY_LIMIT = 3;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var Curl */
    protected $curl;

    protected $apiPath;
    protected $curlHeader;
    protected $lastResponse;
    protected $curlBody;
    protected $requestDataArray;
    protected $requestDataString;
    protected $sslKey;
    protected $sslIv;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        Curl $curl
    ) {
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->commonHelper          = $commonHelper;
        $this->curl                  = $curl;

        $this->requestDataArray  = [];
        $this->requestDataString = "";
    }

    public function requestApiGetCouponList(array $serialNumberArray): array
    {
        $this->curlHeader = null;

        $this->curlHeader = [
            "Content-Type"    => self::CURL_HEADER_CONTENT_TYPE,
            "CID"             => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::YOXI_CONFIG_PATH_CID),
            "APP-VERSION"     => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::YOXI_CONFIG_PATH_APP_VERSION),
            "ENCRYPT-VERSION" => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::YOXI_CONFIG_PATH_ENCRYPT_VERSION),
            "TOKEN"           => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::YOXI_CONFIG_PATH_TOKEN),
        ];

        $requestDataArray = [
            "SerialCodeList" => $serialNumberArray,
        ];

        $responseString = $this->sendRequest(self::API_ROUTE_GET_COUPON_LIST, $requestDataArray);
        $responseArray  = json_decode($responseString, true);

        if (!$this->validateApiResponse($responseString)) {
            $timestamp   = time();
            $decryptData = "";
            if (isset($responseArray["data"]["encryptData"])) {
                $decryptData = $this->getDecryptContentFromResponse($responseArray);
            }

            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting requestApiGetCouponList.",
                "Timestamp"                      => $timestamp,
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request data"                   => $this->curlBody,
                "Response string"                => $responseString,
                "Response content after decrypt" => $decryptData,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            throw new \Exception(json_encode([
                "Message"         => "Something went wrong while requesting Yoxi API({$timestamp}).",
                "Response string" => $responseString,
            ]));
        }

        // response example
        // {"success":true,"error":null,"data":{"encryptData":"aes encrypted string..."}}

        // encryptData content after decrypt example
        // {"CouponList":[{"SerialCode":"HXURE87GBBTZ60R","TotalCount":6,"UsedCount":0,"InvalidateCount":0,"AppliedDateTime":"2024-10-15T11:41:36.000Z","UseLimitedStartDateTime":"2025-03-31T04:33:59.000Z","UseLimitedDateTime":"2025-03-31T04:33:59.000Z","CouponUsedList":[],"CouponInvalidateList":[]},{"SerialCode":"X6A6F8OABMKEDQ0","TotalCount":6,"UsedCount":0,"InvalidateCount":0,"AppliedDateTime":"2024-10-15T11:41:22.000Z","UseLimitedStartDateTime":"2025-03-31T04:33:59.000Z","UseLimitedDateTime":"2025-03-31T04:33:59.000Z","CouponUsedList":[],"CouponInvalidateList":[]}]}

        return $responseArray;
    }

    public function requestApiInvalidateCoupon(array $serialNumberArray): array
    {
        $this->curlHeader = null;

        $this->curlHeader = [
            "Content-Type"    => self::CURL_HEADER_CONTENT_TYPE,
            "CID"             => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::YOXI_CONFIG_PATH_CID),
            "APP-VERSION"     => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::YOXI_CONFIG_PATH_APP_VERSION),
            "ENCRYPT-VERSION" => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::YOXI_CONFIG_PATH_ENCRYPT_VERSION),
            "TOKEN"           => $this->commonHelper->getApiConfigByConfigCode(CommonHelper::YOXI_CONFIG_PATH_TOKEN),
        ];

        $requestDataArray = [
            "SerialCodeList" => $serialNumberArray,
        ];

        $responseString = $this->sendRequest(self::API_ROUTE_INVALIDATE_COUPON, $requestDataArray, "put");
        $responseArray  = json_decode($responseString, true);

        if (!$this->validateApiResponse($responseString)) {
            $timestamp   = time();
            $decryptData = "";
            if (isset($responseArray["data"]["encryptData"])) {
                $decryptData = $this->getDecryptContentFromResponse($responseArray);
            }

            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"                          => "Something went wrong while requesting requestApiInvalidateCoupon.",
                "Timestamp"                      => $timestamp,
                "Request api path"               => $this->apiPath,
                "Request header"                 => $this->curlHeader,
                "Request data before encrypt"    => $this->requestDataArray,
                "Request data"                   => $this->curlBody,
                "Response string"                => $responseString,
                "Response content after decrypt" => $decryptData,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            // 退貨就算沒有殘值也要請求這個API, 所以不要丟出例外
            // throw new \Exception(json_encode([
            //     "Message"         => "Something went wrong while requesting Yoxi API({$timestamp}).",
            //     "Response string" => $responseString,
            // ]));
        }

        // response example
        // {"success":true,"error":null,"data":{"encryptData":"aes encrypted string..."}}

        // encryptData content after decrypt example
        // {"CouponList":[{"SerialCode":"X6A6F8OABMKEDQ0","TotalCount":6,"InvalidateCount":6}]}

        return $responseArray;
    }

    public function getRequestHeader(): null|array
    {
        return $this->curlHeader;
    }

    public function getLastResponse(): null|string
    {
        return $this->lastResponse;
    }

    /**
     * 獲取 $this->requestDataArray 參數值
     * 主要用於查看最近一筆加密前API請求資料(array形式)
     * @return array
     */
    public function getRequestDataArray(): null|array
    {
        return $this->requestDataArray;
    }

    /**
     * 獲取 $this->requestDataString 參數值
     * 主要用於查看最近一筆加密前API請求資料(string形式)
     * @return string
     */
    public function getRequestDataString(): null|string
    {
        return $this->requestDataString;
    }

    /**
     * 送出curl請求
     * @param string $apiRoute
     * @param array $requestDataArray
     * @return string
     */
    private function sendRequest(string $apiRoute, array $requestDataArray, $method = "post"): string
    {
        // Reset prarmeters
        $this->apiPath           = null;
        $this->requestDataArray  = null;
        $this->requestDataString = null;
        $this->curlBody          = null;
        $this->lastResponse      = null;

        // Set api path.
        $domain        = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::YOXI_CONFIG_PATH_API_DOMAIN);
        $domain        = rtrim($domain, "/");
        $this->apiPath = "{$domain}/{$apiRoute}";

        // Set request data(before encrypt).
        $this->requestDataArray  = $requestDataArray;
        $this->requestDataString = json_encode($this->requestDataArray, JSON_UNESCAPED_SLASHES);
        $this->requestDataString = $this->encryptString($this->requestDataString);
        // ------------------------------------------------

        // Set curl body
        $this->curlBody = $this->requestDataString;
        // ------------------------------------------------

        $this->curl->setHeaders($this->curlHeader);
        $this->curl->setTimeout(self::CURL_TIMEOUT_SECONDS);

        // 若curl逾時則以相同參數重送直到重試次數上限
        for ($retryCount = 1; $retryCount <= self::CURL_TIMEOUT_RETRY_LIMIT; $retryCount++) {
            try {
                switch ($method) {
                    case 'get':
                        $this->curl->get($this->apiPath);
                        break;

                    case 'post':
                        $this->curl->post($this->apiPath, $this->curlBody);
                        break;

                    case 'put':
                        $this->curl->put($this->apiPath, $this->curlBody);
                        break;

                    default:
                        throw new \Exception("Unknow request method: " . $method);
                }

                break;
            } catch (\Exception $e) {
                if ($this->isTimeoutException() && !$this->isCurlRetryHitLimit($retryCount)) {
                    continue;
                }

                throw $e;
            }
        }

        $this->lastResponse = $this->curl->getBody();

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
     * @param string $decryptResponseString
     * @return boolean
     */
    private function validateApiResponse(string $decryptResponseString): bool
    {
        $decryptDataAry = json_decode($decryptResponseString, true);

        if (!isset($decryptDataAry["success"])) {
            return false;
        }

        if ($decryptDataAry["success"] != self::API_RESPONSE_CODE_SUCCESS) {
            return false;
        }

        return true;
    }

    public function getDecryptContentFromResponse(array $response): array
    {
        if (!isset($response["data"]["encryptData"])) {
            throw new \Exception("Field 'encryptData' not exist, input:" . json_encode($response));
        }

        return json_decode($this->decryptString($response["data"]["encryptData"]), true);
    }

    public function encryptString(string $string): string
    {
        $aesKey = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::YOXI_CONFIG_PATH_AES_KEY);
        $aesIv  = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::YOXI_CONFIG_PATH_AES_IV);

        $aesString = openssl_encrypt($string, self::ENCRYPT_METHOD, $aesKey, OPENSSL_RAW_DATA, $aesIv);

        return json_encode(["EncryptData" => \base64_encode($aesString)], \JSON_UNESCAPED_SLASHES);
    }

    public function decryptString(string $string): string
    {
        $aesKey = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::YOXI_CONFIG_PATH_AES_KEY);
        $aesIv  = $this->commonHelper->getApiConfigByConfigCode(CommonHelper::YOXI_CONFIG_PATH_AES_IV);

        return openssl_decrypt(\base64_decode($string), self::ENCRYPT_METHOD, $aesKey, OPENSSL_RAW_DATA, $aesIv);
    }
}
