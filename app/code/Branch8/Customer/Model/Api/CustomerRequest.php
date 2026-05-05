<?php
namespace Branch8\Customer\Model\Api;

use Magento\Framework\DataObject;
use Branch8\Customer\Api\CustomerRequestInterface;

class CustomerRequest extends DataObject implements CustomerRequestInterface{

    public function setMemberSeq(string $memberSeq){
        $this->setData(self::MEMBER_SEQ, $memberSeq);
        return $this;
    }

    public function getMemberSeq(){
        return $this->getData(self::MEMBER_SEQ);
    }

    public function setHotai1Name(string $hotai1Name)
    {
        // TODO: Implement setHotai1Name() method.
        $this->setData(self::HOTAI1_NAME, $hotai1Name);
        return $this;
    }

    public function getHotai1Name(){
        return $this->getData(self::HOTAI1_NAME);
    }

}