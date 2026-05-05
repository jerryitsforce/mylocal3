<?php

declare(strict_types=1);

namespace Branch8\Sales\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AllSpecificStatuses implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 0, 'label' => __('All Allowed Statuses')],
            ['value' => 1, 'label' => __('Specific Statuses')]
        ];
    }
}
