<?php

namespace Branch8\HomeDeliveryOrdersAutoComplete\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Shipping\Model\Config\Source\Allmethods;

class ShippingMethodOption implements OptionSourceInterface
{
    protected $shippingMethods;

    public function __construct(
        Allmethods $shippingMethods
    ) {
        $this->shippingMethods = $shippingMethods;
    }

    public function toOptionArray()
    {
        $optionArray = $this->shippingMethods->toOptionArray(true);

        return $optionArray;
    }
}
