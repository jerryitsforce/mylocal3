<?php

declare(strict_types=1);

namespace Branch8\Shipping\Plugin\Order\Tracking;

class View
{
    /**
     * Set rewrite template.
     *
     * @param \Magento\Shipping\Block\Adminhtml\Order\Tracking\View $subject
     *
     * @return void
     */
    public function beforeToHtml(\Magento\Shipping\Block\Adminhtml\Order\Tracking\View $subject): void
    {
        $subject->setTemplate('Branch8_Shipping::order/tracking/view.phtml');
    }
}
