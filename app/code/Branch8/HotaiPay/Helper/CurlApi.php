<?php

namespace Branch8\HotaiPay\Helper;

use Magento\Framework\HTTP\Client\Curl;
use Branch8\HotaiPay\Logger\Api\Logger;
use Branch8\HotaiPay\Helper\Data;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;


/**
 * CurlApi
 */
class CurlApi extends Curl
{
    /** @var \Magento\Framework\HTTP\Client\Curl $curl */
    private $curl;

    /** @var \Branch8\HotaiPay\Helper\Data $configData */
    private $configData;

    /** @var \Branch8\HotaiPay\Logger\Api\Logger $logger */
    private $logger;    

    private HotaiPayLogHelper $hotaiPayLogHelper;


    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        Curl $curl, 
        Data $configData,
        Logger $logger,
        HotaiPayLogHelper $hotaiPayLogHelper
    ){
        $this->curl = $curl;
        $this->configData = $configData;
        $this->logger = $logger;
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
    }    

    /**
     * post
     *
     * @param  mixed $url
     * @param  mixed $body
     * @return void | string | array
     */
    public function post($url, $body) {
        $this->initCurl();
        
        try {
            $this->curl->post($url, $body);
        } catch (\Exception $e) {
            $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);

            throw new \Exception($e->getMessage());
        }
        
        return $this->curl->getBody();
    }
    
    /**
     * initCurl
     *
     * @return void
     */
    protected function initCurl(){
        $this->curl->setHeaders($this->setHeader());
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setOption(CURLOPT_MAXREDIRS, 1);
        $this->curl->setOption(CURLOPT_TIMEOUT, 0);
        $this->curl->setOption(CURLOPT_CONNECTTIMEOUT,  5);
        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
        $this->curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        $this->curl->setOption(CURLOPT_ENCODING, '');
        $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $this->curl->setOption(CURLOPT_VERBOSE, true);

    }
    
    /**
     * setHeader
     *
     * @return void | array
     */
    public function setHeader()
    {
        $headers = [
            'appid' => $this->configData->getAppId(),
            'token' => $this->configData->getToken(),
            'Content-Type' => "application/json"
        ];

        return $headers;
    }
}
?>