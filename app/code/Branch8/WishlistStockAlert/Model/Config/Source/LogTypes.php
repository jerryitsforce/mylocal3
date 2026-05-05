<?php
declare(strict_types=1);

namespace Branch8\WishlistStockAlert\Model\Config\Source;

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
            ['value' => 'exception', 'label' => __('exception.log')],
            ['value' => 'branch8_wishlist_alert_stock', 'label' => __('branch8_wishlist_alert_stock.log')]
        ];
    }
}
