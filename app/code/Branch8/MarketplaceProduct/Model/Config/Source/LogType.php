<?php
declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogType implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'branch8-mproduct.log', 'label' => __('branch8-mproduct.log (var/log/branch8-mproduct.log)')],
            ['value' => 'approveProductByConsumer.log', 'label' => __('approveProductByConsumer.log (var/log/approveProductByConsumer.log)')],
            ['value' => 'marketplace.log', 'label' => __('marketplace.log (var/log/marketplace.log)')],
            ['value' => 'marketplace_version.log', 'label' => __('marketplace_version.log (var/log/marketplace_version.log)')],
            ['value' => 'system.log', 'label' => __('system.log (var/log/system.log)')],
            ['value' => 'exception.log', 'label' => __('exception.log (var/log/exception.log)')]
        ];
    }
}
