<?php

namespace Branch8\HotaiPay\Model\Api;

use Branch8\HotaiPay\Logger\Api\Logger;
use Branch8\HotaiPay\Helper\Crypt;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Branch8\HotaiPay\Helper\Data;
use Branch8\HotaiPay\Helper\ApiEndPointConfig;
use \Magento\Framework\UrlInterface;
use Branch8\HotaiPay\Helper\CurlApi;


/**
 * CreditCard
 */
class CreditCard extends \Branch8\HotaiPay\Model\Api\AbstractModel
{
    /** @var mixed $request */
    protected $request;

    /** @var mixed $request */
    public $creditCardSession;    

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
    )
    {
        parent::__construct($logger, $scopeConfig, $crypt, $configData, $url, $curlApi, $hotaiPayLogHelper);
    }

    /**
     * AddCreditCardManually 手動綁訂
     *
     * @param  mixed $payload
     * @return array
     */
    public function addCreditCardManually(array $payload)
    {
        $requestPayload = [
            "RedirectURL"=> $payload['RedirectURL']
        ];

        $response = $this->getApiResponse(
            ApiEndPointConfig::CREDITCARD_ADD, 
            $requestPayload
        );


        return $response;
    }
   
    /**
     * AddCreditCardFast 快速綁訂
     *
     * @param  mixed $payload
     * @return array
     */
    public function addCreditCardFast(array $payload)
    {
        $requestPayload = [
            "RedirectURL"=> $payload['RedirectURL'],
            "IdNo"=> $payload['IdNo'],
            "Birthday"=> $payload['Birthday']
        ];

        $response = $this->getApiResponse(
            ApiEndPointConfig::CREDITCARD_ADD_FAST, 
            $requestPayload
        );

        return $response;
    }

    /**
     * getCreditCardList 信用卡列表
     *
     * @param  mixed $payload
     * @return array
     */
    public function getCreditCardList(array $payload)
    {
        $requestPayload = [
            "Bin"=> $payload['Bin']
        ];

        $response = $this->getApiResponse(
            ApiEndPointConfig::CREDITCARD_LIST, 
            $requestPayload
        );

        return $response;
    }

    /**
     * editCreditCardAliasName 修改信用卡名稱
     *
     * @param  mixed $payload
     * @return array
     */
    public function editCreditCardAliasName(array $payload)
    {
        $requestPayload = [
            "TokenID" => $payload['TokenID'],
            "AliasName" => $payload['AliasName']
        ];

        $response = $this->getApiResponse(
            ApiEndPointConfig::CREDITCARD_EDIT_ALIASNAME, 
            $requestPayload
        );

        return $response;
    }

     /**
     * deleteCreditCard 刪除信用卡
     *
     * @param  mixed $payload
     * @return array
     */
    public function deleteCreditCard(array $payload)
    {
        $requestPayload = [
            "TokenID" => $payload['TokenID']
        ];

        $response = $this->getApiResponse(
            ApiEndPointConfig::CREDITCARD_DELETE, 
            $requestPayload
        );

        return $response;
    }

    /**
     * getAffinityCard 和泰聯名卡列表
     *
     * @param  mixed $payload
     * @return array
     */
    public function getAffinityCard(array $payload)
    {
        $requestPayload = [
            "Bin" => $payload['Bin']
        ];

        $response = $this->getApiResponse(
            ApiEndPointConfig::CREDITCARD_GET_AFFINITY_LIST, 
            $requestPayload
        );

        return $response;
    }


    /**
     * getManuallyAddCardWarning 手動綁訂提醒文字
     *
     * @param  mixed $payload
     * @return string
     */
    public function getManuallyAddCardWarning()
    {
        $requestPayload = [];

        $response = $this->getApiResponse(
            ApiEndPointConfig::MANUALLY_ADD_CREDITCARD_WARNING, 
            $requestPayload
        );

        return $response;
    }

    /**
     * getFastAddCardWarning 快速綁訂提醒文字
     *
     * @param  mixed $payload
     * @return string
     */
    public function getFastAddCardWarning()
    {
        $requestPayload = [];

        $response = $this->getApiResponse(
            ApiEndPointConfig::FAST_ADD_CREDITCARD_WARNING, 
            $requestPayload
        );

        return $response;
    }
}
?>