<?php

namespace Branch8\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class SaveCustomFieldToOrder implements ObserverInterface
{

    private array $orderAttributes = [
        'distribution_thermosphere',
        'site_free_shipping_threshold',
        'site_shipping_fee',
        'order_free_shipping_threshold',
        'order_note',
        'referrer_code',
        'customer_phone_number'
    ];
    public function execute($observer)
    {
        $order = $observer->getEvent()->getData('order');
        $quote = $observer->getEvent()->getData('quote');

        foreach ($this->orderAttributes as $orderAttribute) {
            if ($quote->hasData($orderAttribute)) {
                $order->setData($orderAttribute, $quote->getData($orderAttribute));
            }
        }
    }

}
