<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Status Options Source
 */
class Status implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label' => __('啟用')],
            ['value' => 0, 'label' => __('關閉')]
        ];
    }
}
