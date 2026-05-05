<?php
namespace Branch8\RewardSystem\Model\Api;

use Magento\Framework\DataObject;

class ApiResponse extends DataObject implements \Branch8\RewardSystem\Api\ApiResponseInterface{

    /**
     * @return int
     */
    public function getIsSuccess(){
        return $this->getData(self::IS_SUCCESS);
    }
    /**
     * @return int
     */
    public function getEventId(){
        return $this->getData(self::EVENT_ID);
    }

    /**
     * @return string
     */
    public function getCustomerIdentify(){
        return $this->getData(self::CUSTOMER_IDENTIFY);
    }
    
    /**
     * @return string
     */
    public function getCreatedAt(){
        return $this->getData(self::CREATE_AT);
    }
    /**
     * @param int $eventId
     * @return $this
     */
    public function setEventId(int $eventId){
        $this->setData(self::EVENT_ID, $eventId);
        return $this;
    }
    /**
     * @param string $customerIdentify
     * @return $this
     */
    public function setCustomerIdentify(string $customerIdentify){
        $this->setData(self::CUSTOMER_IDENTIFY, $customerIdentify);
        return $this;
    }
    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt){
        $this->setData(self::CREATE_AT, $createdAt);
        return $this;
    }

    public function getPartnerIdentify(){
        return $this->getData(self::PARTNER_IDENTIFY);
    }
    /**
     * @param string $createdAt
     * @return $this
     */
    public function setPartnerIdentify(string $partnerIdentify){
        $this->setData(self::PARTNER_IDENTIFY, $partnerIdentify);
        return $this;
    }
    /**
     * @param string $isSuccess
     * @return $this
     */
    public function setIsSuccess(string $isSuccess){
        $this->setData(self::IS_SUCCESS, $isSuccess);
        return $this;
    }
    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }
    /**
     * @param string $isSuccess
     * @return $this
     */
    public function setMessage($message){
        $this->setData(self::MESSAGE, $message);
        return $this;
    }
}
