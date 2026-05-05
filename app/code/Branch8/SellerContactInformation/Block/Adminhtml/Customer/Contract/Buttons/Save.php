<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Contract\Buttons;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
class Save extends Generic implements ButtonProviderInterface
{
    public function getButtonData()
    {
        return [
            'label' => __('Save Contract'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];
    }
}