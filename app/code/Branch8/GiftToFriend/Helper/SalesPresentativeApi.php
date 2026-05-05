<?php
namespace Branch8\GiftToFriend\Helper;

class SalesPresentativeApi extends \Magento\Framework\App\Helper\AbstractHelper
{    

    const API_URL = 'gift_order/sales_presentative_api/url';

    const API_KEY = 'gift_order/sales_presentative_api/key';

    const API_ENABLE = 'gift_order/sales_presentative_api/is_active';

    protected $_curl;

    protected $_logger;

    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Branch8\GiftToFriend\Logger\Logger $logger,
        \Magento\Framework\App\Helper\Context $context
    ){
        parent::__construct($context);
        $this->_curl = $curl;
        $this->_logger = $logger;
    }
    public function pushSalePresentativeApi($data){
        $isNeedToPush = $this->scopeConfig->getValue(self::API_ENABLE);
        if(!$isNeedToPush){
            return ;
        }
        $apiData = [
            'appid' => $data['hotaiAppId'],
            'dealerCode' => $data['dealerCode'],
            'branchCode' => $data['branchCode'],
            'sectionCode' => $data['sectionCode'],
            'salesCode' => $data['salesCode'],
            // 'userID' => $data['userID'],
            'source' => 1,
            'customerId' => $data['customerId'],
            'customerName' => $data['customerName'],
            'custAddress' => $data['custAddress'],
            'customerType' => $data['customerType'],
            'refID' => $data['refID'],
            'refType' => 5,
            'carName' => '',
            'subject' => '贈禮聯繫',
            'purposes' => ['200'],
            'purposeMemo' => $data['purposeMemo'],
            'startTime' => $data['startTime'],
            'endTime' => $data['endTime'],
            'personal' => false,
            'crucial' => false,
            'finish' => false,
            'remind' => false,
            'result' => $data['result'],
            'resultMemo' => $data['resultMemo'],
            'isAuto' => true
        ];
        
        return $this->callApi($apiData);
    }

    public function callApi($apiData){
        $this->writeLog('Sales Presentative API: Request');
        $this->writeLog(print_r($apiData, true));
        $this->_curl->setTimeout(5);

        $this->_curl->addHeader("Content-Type", "application/json");
        $this->_curl->addHeader("Accept", "application/json");
        try{
            $apiKey = $this->scopeConfig->getValue(self::API_KEY);
            $this->_curl->addHeader("apikey", $apiKey);
            
            $requestURL = $this->scopeConfig->getValue(self::API_URL);
            $this->_curl->post($requestURL, json_encode($apiData));
            $response = $this->_curl->getBody();

            $this->writeLog('Sales Presentative: response');
            $this->writeLog(print_r($response, true));
            return $response;
        }catch(\Exception $e){
            $this->writeLog('Sales Presentative: response exception');
            $this->writeLog($e->getMessage());
        }
        return false;
    }

    protected function writeLog($message){
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_GiftToFriend', 'sale_presentative_api')){
            $this->_logger->info($message);
        }
    }
}
