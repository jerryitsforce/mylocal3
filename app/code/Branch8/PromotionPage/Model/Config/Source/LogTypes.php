<?php
declare(strict_types=1);

namespace Branch8\PromotionPage\Model\Config\Source;

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
            ['value' => 'promotionPageLoggerDebug', 'label' => __('promotionPageLoggerDebug.log')]
        ];
    }
}
