<?php

namespace Branch8\BrandManagement\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogOption implements OptionSourceInterface
{
    /**
     * Provide a Yes/No switch for enabling BrandManagement logs.
     *
     * @return array<int, array{value: int, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('No')],
            ['value' => 1, 'label' => __('Yes')],
        ];
    }
}

