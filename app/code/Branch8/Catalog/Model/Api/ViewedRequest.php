<?php
namespace Branch8\Catalog\Model\Api;

use Magento\Framework\DataObject;
use Branch8\Catalog\Api\ViewedRequestInterface;

class ViewedRequest extends DataObject implements ViewedRequestInterface{

    public function setMemberSeq(string $memberSeq){
        $this->setData(self::MEMBER_SEQ, $memberSeq);
        return $this;
    }

    public function getSku(){
        return $this->getData(self::SKU);
    }

    public function setSku(string $sku)
    {
        // TODO: Implement setHotai1Name() method.
        $this->setData(self::SKU, $sku);
        return $this;
    }

    public function getMemberSeq(){
        return $this->getData(self::MEMBER_SEQ);
    }

}