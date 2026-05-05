<?php

namespace Branch8\OptionsWithStockAndImages\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogType implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'debug_saas_price_provider_error.log', 'label' => __('debug_saas_price_provider_error.log (var/log/debug_saas_price_provider_error.log)')],
            ['value' => 'wkosi.log', 'label' => __('wkosi.log (var/log/wkosi.log)')],
            ['value' => 'variations-fix-ready-to-ship-qty.log', 'label' => __('variations-fix-ready-to-ship-qty.log (var/log/variations-fix-ready-to-ship-qty.log)')],
            ['value' => 'system.log', 'label' => __('system.log (var/log/system.log)')],
            ['value' => 'exception.log', 'label' => __('exception.log (var/log/exception.log)')]
        ];
    }
}
