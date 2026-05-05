<?php
namespace Branch8\TigrenSplitCart\Plugin\Magento\Quote\Model\Quote\Item;

class AbstractItem{

    public function aroundCheckData($subject, $proceed){
        $subject->setHasError(false);
        $subject->clearMessage();
        if(!$subject->getData('available_to_checkout')){
            return $subject;
        }else{
            return $proceed();
        }
    }
}