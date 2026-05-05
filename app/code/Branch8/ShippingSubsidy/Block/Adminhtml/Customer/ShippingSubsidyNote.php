<?php

declare(strict_types=1);

namespace Branch8\ShippingSubsidy\Block\Adminhtml\Customer;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class ShippingSubsidyNote extends Template
{
    protected function _toHtml(): string
    {
        $configUrl = $this->getUrl('adminhtml/system_config/edit', ['section' => 'shipping_subsidy']);

        return '<div class="message message-notice">'
            . __('The "Use Default Config" checkbox is checked by default. When checked, all options follow the settings in <a href=\'%1\'>Store > Configuration > Branch8 > Shipping Subsidy</a>.<br/>If you want to modify, please uncheck it and edit individually.', $configUrl)
            . '</div>';
    }
}
