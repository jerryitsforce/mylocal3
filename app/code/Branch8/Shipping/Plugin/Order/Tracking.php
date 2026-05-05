<?php

declare(strict_types=1);

namespace Branch8\Shipping\Plugin\Order;

use Magento\Shipping\Block\Adminhtml\Order\Tracking as BaseTracking;

class Tracking
{
    /**
     * Returns custom carries only.
     *
     * @param BaseTracking $subject
     * @param array $result
     *
     * @return array
     */
    public function afterGetCarriers(BaseTracking $subject, array $result): array
    {
        return ['custom' => __('Custom Value')];
    }
}
