<?php
namespace Branch8\RewardSystem\Model\Api;

use Magento\Framework\DataObject;

class ApiRequest extends DataObject implements \Branch8\RewardSystem\Api\ApiRequestInterface
{
    public function setApiKey(string $apiKey){
        $this->setData(self::API_KEY, $apiKey);
    }

    public function getApiKey(){
        return $this->getData(self::API_KEY);
    }

    public function setCustomerIdentify(string $customerIdentify){
        $this->setData(self::CUSTOMER_IDENTIFY, $customerIdentify);
    }

    public function getCustomerIdentify(){
        return $this->getData(self::CUSTOMER_IDENTIFY);
    }

    public function getPartnerIdentify(){
        return $this->getData(self::PARTNER_IDENTIFY);
    }

    public function setPartnerIdentify($partnerIdentify){
        $this->setData(self::PARTNER_IDENTIFY, $partnerIdentify);
    }
}
