<?php

namespace Branch8\HifiSalesReport\Helper;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Helper\Curl;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Api
{
    const LOG_FOLDER_NAME = 'HifiSalesReport/Api';

    const CONFIG_PATH_API_DOMAIN = "hifi_sales_report/api/api_domain";

    const API_ROUTE_POST_HIFI = "api/Hifi/PostHifi";

    const API_RESPONSE_CODE_SUCCESS = "匯入成功";

    const CURL_TIMEOUT_ERROR_CODE  = 28;
    const CURL_TIMEOUT_SECONDS     = 30;
    const CURL_TIMEOUT_RETRY_LIMIT = 3;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var Curl */
    protected $curl;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    public $apiPath;
    public $curlHeader;
    public $curlBody;
    public $requestDataArray;
    public $requestDataString;
    public $lastResponse;

    public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        Curl $curl,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->curl                  = $curl;
        $this->scopeConfig           = $scopeConfig;
    }

    public function requestPostHifi(array $requestData): string|array
    {
        $response = $this->sendRequest(self::API_ROUTE_POST_HIFI, $requestData);

        if (!$this->validateApiResponse($response)) {
            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                "Title"            => "Something went wrong while requesting PostHifi.",
                "Request api path" => $this->apiPath,
                "Request header"   => $this->curlHeader,
                "Request body"     => $this->requestDataArray,
                "Response"         => $response,
            ], \JSON_UNESCAPED_SLASHES), self::LOG_FOLDER_NAME);

            throw new \Exception(json_encode([
                "Message"  => "Something went wrong while requesting PostHifi.",
                "Response" => $response,
            ]));
        }

        // responseString:
        // 成功

        return $response;
    }

    /**
     * 判斷API回傳的狀態碼是否成功
     * @param string $decryptResponseString
     * @return boolean
     */
    private function validateApiResponse(string $response): bool
    {
        return $response == self::API_RESPONSE_CODE_SUCCESS;
    }

    /**
     * 送出curl請求
     * @param string $apiRoute
     * @param array $requestDataArray
     * @return string
     */
    public function sendRequest(string $apiRoute, array $requestDataArray): string
    {
        // Reset prarmeters
        $this->apiPath           = null;
        $this->curlHeader        = null;
        $this->requestDataArray  = null;
        $this->requestDataString = null;
        $this->curlBody          = null;
        $this->lastResponse      = null;

        // Set api path.
        $domain        = $this->scopeConfig->getValue(self::CONFIG_PATH_API_DOMAIN);
        $domain        = rtrim($domain, "/");
        $this->apiPath = "{$domain}/{$apiRoute}";

        // Set header.
        $this->curlHeader = [
            "Content-Type" => "application/json",
        ];
        // ------------------------------------------------

        // Set request data(before encrypt).
        $this->requestDataArray  = $requestDataArray;
        $this->requestDataString = json_encode($this->requestDataArray);
        // ------------------------------------------------

        // Set curl body
        $this->curlBody = json_encode($this->requestDataArray);
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

        $this->lastResponse = $this->curl->getBody();

        return $this->lastResponse;
    }

    /**
     * 確認當前例外是否屬於逾時例外
     * @return boolean
     */
    private function isTimeoutException(): bool
    {
        return $this->curl->getErrno() == self::CURL_TIMEOUT_ERROR_CODE;
    }

    /**
     * 確認重新請求嘗試次數是否已達到上限
     * @param integer $currentRetryCount
     * @return boolean
     */
    private function isCurlRetryHitLimit(int $currentRetryCount): bool
    {
        return $currentRetryCount >= self::CURL_TIMEOUT_RETRY_LIMIT;
    }
}
