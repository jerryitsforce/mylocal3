<?php

namespace Branch8\EventTicket\Block\Adminhtml\Event\Edit\Buttons;

use Branch8\Customer\Block\Adminhtml\Organization\Edit\Buttons\Generic;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Save extends Generic implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];
    }
}