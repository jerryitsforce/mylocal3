<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Log types source model
 */
class LogTypes implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'system', 'label' => __('system.log')],
            ['value' => 'exception', 'label' => __('exception.log')]
        ];
    }
}
