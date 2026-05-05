<?php

namespace Branch8\PointMoneyCollect\Observer;

use Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigFreeRatioRedeemLimitType;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Branch8\PointMoneyConfig\Helper\Common;

class IsCompleteApplyPoint implements ObserverInterface{

    protected $pointHelperData;
    public function __construct(
        \Branch8\PointMoneyCollect\Helper\Data $pointHelperData
    ){
        $this->pointHelperData = $pointHelperData;
    }

    public function execute(Observer $observer){
        $event = $observer->getEvent();
        $quote = $event->getQuote();
        if(!$this->pointHelperData->isValidPointApply($quote)){
            $message = __('Some products need to apply point to checkout.');
            throw new \Magento\Framework\Exception\LocalizedException(__($message));
        }
    }
}