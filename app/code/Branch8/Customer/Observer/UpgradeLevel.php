<?php

namespace Branch8\Customer\Observer;

class UpgradeLevel implements \Magento\Framework\Event\ObserverInterface{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Branch8\Customer\Helper\Group
     */
    protected $groupHelper;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Branch8\Customer\Helper\Group $groupHelper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Branch8\Customer\Helper\Group $groupHelper
    ){
        $this->scopeConfig = $scopeConfig;
        $this->groupHelper = $groupHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer){
        $order = $observer->getOrder();
        $orderStatus = $order->getStatus();
        $finalOrderStatus = $this->scopeConfig->getValue(\Branch8\Customer\Helper\Group::CONFIG_FINAL_ORDER_SUCCESS_STATUS);
        $customerId = $order->getCustomerId();
        if($orderStatus == $finalOrderStatus){
            $action = 'Order complete';
            $this->groupHelper->upgradeLevel($customerId, $action);
        }
    }

}