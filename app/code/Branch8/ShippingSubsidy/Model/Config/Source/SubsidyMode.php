<?php

/**
 * Copyright © 2025 Branch8. All rights reserved.
 */

declare(strict_types=1);

namespace Branch8\ShippingSubsidy\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SubsidyMode implements OptionSourceInterface
{
    public const MODE_PERCENT = 'percent';
    public const MODE_FIXED = 'fixed';

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::MODE_PERCENT, 'label' => __('Percentage')],
            ['value' => self::MODE_FIXED, 'label' => __('Fixed Amount')]
        ];
    }
}
