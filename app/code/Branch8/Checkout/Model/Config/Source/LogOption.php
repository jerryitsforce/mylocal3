<?php
namespace Branch8\Checkout\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            ['value' => 'exceptionlog', 'label' => __('Exception Log(var/log/exception.log)')],
            ['value' => 'quote_address', 'label' => __('Quote Address Log(var/log/Checkout/Addresses/{Y_m_d}.log)')],
            ['value' => 'hotaipay_gtt0', 'label' => __('Hotaipay - GrandTotal Log(var/log/hotaipay_gtt0.log)')],
            ['value' => 'predispatch_checkout_cart', 'label' => __('PredispatchCheckoutCart (var/log/Checkout/Observer/PredispatchCheckoutCart/{Y_m_d}.log)')],
            ['value' => 'order_address', 'label' => __('Order Address Log(var/log/Checkout/OrderAddresses/{Y_m_d}.log)')],
        ];
    }
}
