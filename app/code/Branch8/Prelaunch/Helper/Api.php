<?php

namespace Branch8\Prelaunch\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Math\Random;

class Api extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $logger;
    protected $_curl;

    protected $_scopeConfig;

    /**
     * @var Random
     */
    private Random $random;

    const API_URL = 'prelaunch/general/api_url';
    protected $logFilename = '/var/log/os_api.log';

    protected $prlaunchHelperData;
    public function __construct(
        Context $context,
        \Zend_Log $zendLog,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Branch8\Prelaunch\Helper\Data $prlaunchHelperData,
        Random $random
    )
    {
        parent::__construct($context);
        $writer = new \Zend_Log_Writer_Stream(BP . $this->logFilename);
        $this->logger = $zendLog;
        $this->logger->addWriter($writer);

        $this->_curl = $curl;
        $this->_scopeConfig = $scopeConfig;
        $this->prlaunchHelperData = $prlaunchHelperData;
        $this->random = $random;
    }

    public function stockAPI($requestData)
    {
        $requestAPIData = json_encode($requestData);
        /**
         * success = -1: Did no things
         * success = 0: exeption
         * success = 1: success
         */
        $returnData = [
            'success' => -1,
            'response' => ''
        ];

        $isActive = $this->prlaunchHelperData->isActive();
        if(!$isActive){
            return $returnData;
        }

        try {
            $requestKey = $this->random->getRandomNumber(0, 1000000);
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Prelaunch', 'old_system_stock')){
                $this->logger->info('Start Key: ' . $requestKey);
                $this->logger->info(print_r($requestAPIData, true));
            }

            $this->_curl->setTimeout(3);
            $this->_curl->addHeader("Content-Type", "application/json");
            $requestURL = $this->_scopeConfig->getValue(self::API_URL);
            $this->_curl->post($requestURL, $requestAPIData);
            $response = $this->_curl->getBody();
            $returnData['response'] = $response;
            $returnData['success'] = 1;

            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Prelaunch', 'old_system_stock')){
                $this->logger->info('' . $requestKey);
                $this->logger->info(print_r($response, true));
            }
        }catch(\Exception $e){
            $returnData['success'] = 0;
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Prelaunch', 'old_system_stock')){
                $this->logger->info('' . $requestKey);
                $this->logger->info($e->getMessage());
            }
        }
        return $returnData;
    }


}
