<?php

namespace Branch8\Checkout\Plugin\Quote\Model\Quote;

use C4B\FreeProduct\SalesRule\Action\GiftAction;

class AutoSetAvailableToCheckoutForGiftCart
{

    public function afterAddProduct($subject, $result){
        if($result instanceof \Magento\Quote\Model\Quote\Item){
            if($result->getOptionByCode(GiftAction::ITEM_OPTION_UNIQUE_ID) instanceof \Magento\Quote\Model\Quote\Item\Option){
                $result->setAvailableToCheckout(1);
            }
        }

        return $result;
    }

}