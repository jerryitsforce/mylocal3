<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Block\Adminhtml\Type\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class BackButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        return [
            'label'    => __('Back'),
            'on_click' => 'javascript:history.go(-1)',
            'class'    => 'back',
            'sort_order' => 10,
        ];
    }
}
