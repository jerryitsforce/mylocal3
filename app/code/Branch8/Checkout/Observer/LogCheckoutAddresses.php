<?php

namespace Branch8\Checkout\Observer;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Magento\Framework\Event\Observer;

class LogCheckoutAddresses implements \Magento\Framework\Event\ObserverInterface {

    const LOG_PATH = 'Checkout/OrderAddresses';

    /** 
     * @var HotaiCoreCommon 
     */
    protected $hotaiCoreCommon;

    /**
     * Constructor
     * @param HotaiCoreCommon $hotaiCoreCommon
     */
    public function __construct(
        HotaiCoreCommon $hotaiCoreCommon
    ){
        $this->hotaiCoreCommon = $hotaiCoreCommon;
    }

       /**
     * Execute observer
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer){
        $order = $observer->getEvent()->getOrder();
        
        if ($order) {
            $this->writeLog('After submiting order -------');
            // Log the shipping method
            $shippingMethod = $order->getShippingMethod();
            $this->writeLog('Shipping Method: ' . $shippingMethod);
            
            // Log shipping address
            $shippingAddress = $order->getShippingAddress();
            if ($shippingAddress) {
                $this->writeLog('Shipping Address: ' . json_encode($shippingAddress->getData()));
            }

            // Log billing address
            $billingAddress = $order->getBillingAddress();
            if ($billingAddress) {
                $this->writeLog('Billing Address: ' . json_encode($billingAddress->getData()));
            }

            // Log the order ID
            $this->writeLog('Order ID: ' . $order->getIncrementId());
            $this->writeLog('-------');
        }
    }
    
    private function writeLog($message)
    {
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Checkout', 'order_address')){
            $this->hotaiCoreCommon->writeLog(
                $message,
                self::LOG_PATH
            );
        }
    }

}