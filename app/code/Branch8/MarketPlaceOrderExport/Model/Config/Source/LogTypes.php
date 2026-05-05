<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExport\Model\Config\Source;

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
            ['value' => 'auditExportOrder', 'label' => __('auditExportOrder.log')]
        ];
    }
}
