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
 * Payment
 */
class Payment extends \Branch8\HotaiPay\Model\Api\AbstractModel
{    

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
    )
    {
        parent::__construct($logger, $scopeConfig, $crypt, $configData, $url, $curlApi, $hotaiPayLogHelper);
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
    }
       
    /**
     * checkout
     *
     * @param  mixed $payload
     * @return array
     */
    public function checkout(array $payload)
    {
        $requestPayload = [
            "TokenID" => $payload['TokenID'],
            "MerchantID" => $this->configData->getCheckoutMerchantId(),
            "MerID"=> $this->configData->getCheckoutMerId(),
            "TerminalID"=> $this->configData->getCheckoutTerminalId(),
            "Lidm"=> $payload['Lidm'],
            "PurchAmt"=> $payload['PurchAmt'],
            "TxType"=> $payload['TxType'],
            "AutoCap"=> $payload['AutoCap'],
            "RedirectURL"=> $payload['RedirectURL']
        ];

        $this->hotaiPayLogHelper->writeLog('[Request] ' . json_encode($requestPayload, JSON_UNESCAPED_UNICODE), __CLASS__);

        $response = $this->getApiResponse(ApiEndPointConfig::CHECKOUT_PAY, $requestPayload);

        $this->hotaiPayLogHelper->writeLog('[Response] ' . json_encode($response, JSON_UNESCAPED_UNICODE), __CLASS__);

        return $response;
    }

    /**
     * inquiry
     *
     * @param  mixed $payload
     * @return array
     */
    public function inquiry(array $payload)
    {
        $requestPayload = [
            "orderId" => $payload['orderId']
        ];

        $this->hotaiPayLogHelper->writeLog('[Request] ' . json_encode($requestPayload, JSON_UNESCAPED_UNICODE), __CLASS__);

        $response = $this->getApiResponse(ApiEndPointConfig::PAYMENT_INQUIRY, $requestPayload);

        $this->hotaiPayLogHelper->writeLog('[Response] ' . json_encode($response, JSON_UNESCAPED_UNICODE), __CLASS__);
        
        return $response;
    }
}
?>