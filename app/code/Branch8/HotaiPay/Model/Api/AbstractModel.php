<?php

namespace Branch8\HotaiPay\Model\Api;

use Branch8\HotaiPay\Helper\ApiEndPointConfig;
use Branch8\HotaiPay\Helper\Crypt;
use Branch8\HotaiPay\Helper\CurlApi;
use Branch8\HotaiPay\Helper\Data;
use Branch8\HotaiPay\Helper\ErrorMsg;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;
use Branch8\HotaiPay\Logger\Api\Logger;
use Exception;
use Magento\Framework\DataObject;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\UrlInterface;

/**
 * AbstractModel
 */
class AbstractModel extends DataObject
{
    const retryTimes = 5;

    /** @var \Branch8\HotaiPay\Helper\Data $configData */
    public $configData;

    /** @var \Branch8\HotaiPay\Logger\Api\Logger $logger */
    private $logger;

    /** @var \Branch8\HotaiPay\Helper\Crypt $crypt */
    public $crypt;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig */
    public $scopeConfig;

    /** @var mixed $config */
    public $config;

    /** @var \Magento\Framework\UrlInterface $url */
    public $url;

    /** @var mixed $requestMethod */
    protected $requestMethod;

    /** @var mixed $apiEndPoint */
    protected $apiEndPoint;

    /** @var \Branch8\HotaiPay\Helper\CurlApi $curlApi */
    protected $curlApi;

    /** @var void|string $apiUrl */
    private $apiUrl;

    private HotaiPayLogHelper $hotaiPayLogHelper;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        crypt $crypt,
        Data $configData,
        UrlInterface $url,
        CurlApi $curlApi,
        HotaiPayLogHelper $hotaiPayLogHelper
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->crypt = $crypt;
        $this->configData = $configData;
        $this->url = $url;
        $this->curlApi = $curlApi;
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
    }

    /**
     * setEncryptBody
     *
     * @param  mixed $array
     * @param  mixed $charCode
     * @return void | string
     */
    public function setEncryptBody(array $array, string $charCode)
    {
        $bodyEncrypt = $this->crypt->encrypt(
            $array,
            $charCode
        );
        return base64_encode($bodyEncrypt);
    }

    /**
     * setRequestMethod
     *
     * @param  mixed $requestMethod
     * @return void
     */
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;
    }

    /**
     * setApiEndPoint
     *
     * @param  mixed $apiEndPoint
     * @return void
     */
    public function setApiEndPoint($apiEndPoint)
    {
        $this->apiEndPoint = $apiEndPoint;
    }

    /**
     * setApiUrl
     *
     * @return void
     */
    public function setApiUrl()
    {
        $this->apiUrl = $this->configData->getPaymentUrl();
    }

    /**
     * postRequest
     *
     * @param  mixed $requestPayload
     * @return void | string
     */
    public function postRequest(array $requestPayload)
    {
        try {
            /**
             * set api url
             */
            $this->setApiUrl();

            /**
             * set logger data
             */

            $this->hotaiPayLogHelper->writeLog('######### Start API: Endpoint - ' . $this->apiEndPoint . ' ########', __CLASS__);

            $this->hotaiPayLogHelper->writeLog('[Request] ' . json_encode($requestPayload, JSON_UNESCAPED_UNICODE), __CLASS__);

            /**
             * set request encrypt body
             */
            $bodyEncrypt = $this->setEncryptBody(
                $requestPayload,
                $this->crypt::CHAR_CODE_BASE64
            );

            /**
             * set request body format
             */
            $body = json_encode(
                [
                    'Body' => $bodyEncrypt,
                    'Method' => $this->requestMethod,
                    'API' => $this->apiEndPoint,
                ]
            );

            /**
             * post request
             */
            $response = $this->postApi($body);
            $response = is_array($response) ? json_encode($response, JSON_UNESCAPED_UNICODE) : $response;

            $this->hotaiPayLogHelper->writeLog('[Post Response] '. $this->apiEndPoint . '  ' . $response, __CLASS__);

            return $response;
        } catch (\Exception $e) {
            $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);
        }
    }

    /**
     * postApi
     *
     * @param  mixed $body
     * @param  mixed $retryTimes
     * @return void | array| string
     */
    private function postApi(string $body, int $retryTimes = self::retryTimes)
    {
        if ($retryTimes == 0) {
            $this->hotaiPayLogHelper->writeLog("Reach Retry Times.", __CLASS__);

            return ErrorMsg::SSL_CONNECT_ERROR;
        }

        try {
            $this->hotaiPayLogHelper->writeLog('[ApiUrl] '. $this->apiUrl, __CLASS__);

            $this->hotaiPayLogHelper->writeLog('[Body] '. $body, __CLASS__);

            return $this->curlApi->post($this->apiUrl, $body);
        } catch (\Exception $e) {

            $this->hotaiPayLogHelper->writeLog("-----POST HOTAI PAY API EXCEPTION ----", __CLASS__);

            $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);

            if ($retryTimes > 0) {
                $retryTimes = $retryTimes - 1;
                return $this->postApi($body, $retryTimes);
            }
        }
    }

    /**
     * decryptResponse
     *
     * @param  mixed $response
     * @return void | array
     */
    public function decryptResponse($response)
    {

        /**
         * Error Msg
         */
        if (is_array(json_decode($response, true))) {
            
            $this->hotaiPayLogHelper->writeLog('[Response] ' . $this->apiEndPoint . '  ' .json_encode($response, JSON_UNESCAPED_UNICODE), __CLASS__);

            return json_decode($response, true);
        }

        /**
         * This site is not in whitelist.
         */
        if (str_contains($response, 'blocked')) {
            throw new \Exception('Please Add This Site To Whitelist.');
        }

        /**
         * decrypt response
         */
        $decryptResponse = $this->crypt->decrypt(
            $response,
            $this->crypt::CHAR_CODE_BASE64
        );

        //If there is response
        if ($decryptResponse) {
            $responseArray = json_decode($decryptResponse, true);

            try {
                $this->hotaiPayLogHelper->writeLog('[Decrypt Response] ' . $this->apiEndPoint . '  ' .json_encode($responseArray, JSON_UNESCAPED_UNICODE), __CLASS__);
            } catch (Exception $e) {
                $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);
            }

            return json_decode($decryptResponse, true);
        }

        return $response;
    }

    /**
     * getApiResponse
     *
     * @param  mixed $apiEndpont
     * @param  mixed $requestPayload
     * @param  mixed $retryTimes
     * @return array
     */
    public function getApiResponse(string $apiEndpont, array $requestPayload)
    {
        /** Check Token */
        $token = $this->configData->getToken();

        if (!$token || is_null($token)) {
            $message = 'There is no Customer Token. Please Sign in Again.';
            $this->hotaiPayLogHelper->writeLog($message, __CLASS__);

            throw new Exception(__($message));
        }

        /**
         * Set Api Endpoint
         */
        $this->setApiEndPoint($apiEndpont);
        $apiConfig = ApiEndPointConfig::API_AND_NEEDED_RESPONSE_DATA[$this->apiEndPoint];

        try {

            //set method
            $this->setRequestMethod($apiConfig[ApiEndPointConfig::METHOD]);

            // get response
            $response = $this->postRequest($requestPayload);

            return $this->decryptResponse($response);
        } catch (\Exception $e) {
            $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);
            return [];
        }
    }
}
