<?php

namespace Branch8\MarketplaceProduct\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PreorderMode implements OptionSourceInterface
{
    const START_END_DATE = 1;

    const X_DAYS = 2;

    const SPECIFY_SHIPPING_DATE = 3;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Start/ End date'), 'value' => self::START_END_DATE],
            ['label' => __('X Days after ordering'), 'value' => self::X_DAYS],
            ['label' => __('Specify Shipping Date'), 'value' => self::SPECIFY_SHIPPING_DATE],
        ];
    }
}
