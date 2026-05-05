<?php

declare(strict_types=1);

namespace Branch8\Sales\Plugin\Marketplace\Block\Order;

use Webkul\Marketplace\Block\Order\View as BaseView;

class View
{
    /**
     * Returns custom carries only.
     *
     * @param BaseView $subject
     * @param array $result
     *
     * @return array
     */
    public function afterGetCarriers(BaseView $subject, array $result): array
    {
        return ['custom' => __('Custom Value')];
    }
}
