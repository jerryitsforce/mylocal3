<?php

namespace Branch8\Customer\Model;

class Customer extends \Magento\Customer\Model\Customer{

    public function beforeSave()
    {
        if($this instanceof \Magento\Customer\Model\Customer && $this->getData('platform') != 'seller'){
            if($this->getOrigData('email') != '') {
                $this->setData('email', $this->getOrigData('email'));
            }
        }
        return parent::beforeSave();
    }

}