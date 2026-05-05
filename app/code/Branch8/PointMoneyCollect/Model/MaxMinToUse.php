<?php

namespace Branch8\PointMoneyCollect\Model;
use \Magento\Checkout\Model\ConfigProviderInterface;
class MaxMinToUse implements ConfigProviderInterface{

    protected $pointHelper;

    public function __construct(
        \Branch8\PointMoneyCollect\Helper\Data $pointHelper,
    ){
        $this->pointHelper = $pointHelper;
    }

    public function getConfig()
    {
        $pointData = $this->pointHelper->getPointUsedTotal();
        $pointData['customer_point'] = $this->pointHelper->getCustomerPoints();
        $additionalVariables['point_discount_info'] = $pointData;
        return $additionalVariables;
    }

}
