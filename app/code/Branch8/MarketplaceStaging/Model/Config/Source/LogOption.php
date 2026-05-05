<?php
namespace Branch8\MarketplaceStaging\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            ['value' => 'systemlog', 'label' => __('System Log(var/log/system.log)')],
            ['value' => 'exceptionlog', 'label' => __('Exception Log(var/log/exception.log)')],
            ['value' => 'mplog', 'label' => __('Marketplace Log(var/log/marketplace.log)')],
            ['value' => 'variantions', 'label' => __('Variations Log(var/log/wkosi.log)')]
        ];
    }
}
