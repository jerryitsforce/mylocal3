<?php

namespace Branch8\Customer\Model\Api;

use Branch8\Customer\Api\CustomerResponseInterface;
use Magento\Framework\DataObject;

class CustomerResponse extends DataObject implements CustomerResponseInterface
{
      /**
     * @return string
     */
    public function getResult(){
        return $this->getData(self::RESULT);
    }

    /**
     * @param string $result
     * @return this
     */
    public function setResult(string $result){
        $this->setData(self::RESULT, $result);
        return $this;
    }
}