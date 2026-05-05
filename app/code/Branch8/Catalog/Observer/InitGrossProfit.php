<?php

namespace Branch8\Catalog\Observer;

use Magento\Downloadable\Model\Product\Type;
use Magento\Framework\Event\ObserverInterface;

class InitGrossProfit implements ObserverInterface{


    public function execute($observer){
        $product = $observer->getEvent()->getProduct();
        if($product->getId()){
            return;
        }
        //only save for first time
        if(!in_array($product->getTypeId(), [
            \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
            \Magento\GiftCard\Model\Giftcard::TYPE_VIRTUAL,
            Type::TYPE_DOWNLOADABLE])
        ){
            return;
        }
        $commissionRate = $product->getCommissionRate();
        if(!$product->getCommissionRate()){// != admin/ seller edit on frontend, can be import
            $cost = $product->getCost();
            $specialPrice = $product->getSpecialPrice();
            $priceToCalc = $product->getPrice();
            if($specialPrice){
                $priceToCalc = $specialPrice;
            }
            $commissionRate = ($priceToCalc - $cost)/$priceToCalc*100;
        }
        $product->setInitGrossProfit($commissionRate);
    }

}