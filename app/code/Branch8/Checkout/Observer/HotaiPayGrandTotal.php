<?php
namespace Branch8\Checkout\Observer;

class HotaiPayGrandTotal implements \Magento\Framework\Event\ObserverInterface{


    public function execute($observer){
        /**
         * @var $masterQuote \Magento\Quote\Model\Quote
         */
        $masterQuote = $observer->getEvent()->getData('quote');
        $quotePaymentMethod = $masterQuote->getPayment()->getMethod();
        if($quotePaymentMethod == 'hotaipay' && $masterQuote->getGrandTotal() == 0.0){
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Checkout', 'hotaipay_gtt0')){
                $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/hotaipay_gtt0.log');
                $logger = new \Zend_Log();
                $logger->addWriter($writer);
                $logData = [
                    'quote_id' => $masterQuote->getId(),
                    'total_point_applied' => $masterQuote->getPointUsedTotal(),
                    'point_discount_total' => $masterQuote->getPointDiscountTotal(),
                    'customer_id' => $masterQuote->getCustomerId()
                ];
                $logger->info(print_r($logData, true));
            }
            throw new \Exception('Error on payment method, please refresh page and try again.');
        }
    }
}