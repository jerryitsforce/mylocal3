<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use HotaiConnected\Logistics\Model\Config\LogisticsCompany;

/**
 * Logistics Company Options Source
 */
class Company implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => '', 'label' => __('-- 請選擇物流商 --')]
        ];

        return array_merge($options, LogisticsCompany::getOptionArray());
    }
}
