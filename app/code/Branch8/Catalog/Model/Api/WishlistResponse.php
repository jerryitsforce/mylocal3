<?php

namespace Branch8\Catalog\Model\Api;

use Branch8\Catalog\Api\WishlistResponseInterface;
use Magento\Framework\DataObject;

class WishlistResponse extends DataObject implements WishlistResponseInterface
{
    /**
     * @return bool
     */
    public function getError(){
        return $this->getData(self::ERROR);
    }

    /**
     * @param bool $error
     * @return this
     */
    public function setError(bool $error){
        $this->setData(self::ERROR, $error);
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(){
        return $this->getData(self::MESSAGE);
    }

    /**
     * @param string $message
     * @return this
     */
    public function setMessage(string $message){
        $this->setData(self::MESSAGE, $message);
        return $this;
    }
}