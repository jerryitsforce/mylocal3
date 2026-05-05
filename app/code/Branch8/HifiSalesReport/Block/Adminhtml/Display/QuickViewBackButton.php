<?php

namespace Branch8\HifiSalesReport\Block\Adminhtml\Display;

use Magento\Backend\Block\Widget\Container;

class QuickViewBackButton extends Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->addButton(
            'back',
            [
                'label'   => __('Back'),
                'onclick' => 'setLocation(document.referrer)',
                'class'   => 'back',
            ],
            -1
        );
    }
}