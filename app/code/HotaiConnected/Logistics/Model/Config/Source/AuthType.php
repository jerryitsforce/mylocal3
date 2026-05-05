<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class AuthType implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'account', 'label' => __('帳號密碼')],
            ['value' => 'api_key', 'label' => __('API Key')],
            ['value' => 'token', 'label' => __('Token')]
        ];
    }
}
