<?php
declare(strict_types=1);

namespace Branch8\MarketplaceParentOrderRetryCancel\Model\Config\Source;

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
            ['value' => 'retryCancelParentHandleLoggerDebug', 'label' => __('retryCancelParentHandleLoggerDebug.log')]
        ];
    }
}
