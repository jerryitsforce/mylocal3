<?php
namespace Branch8\CatalogRule\Model\Config\Source;

class ApproveType implements \Magento\Framework\Option\ArrayInterface
{
    const TYPE_NEW = 1;

    const TYPE_EDIT = 2;

    const TYPE_STAGING = 3;

    public function toOptionArray()
    {
        return [
            ['value' => self::TYPE_NEW, 'label' => __('New Rule')],
            ['value' => self::TYPE_EDIT, 'label' => __('Modify')],
            ['value' => self::TYPE_STAGING, 'label' => __('Schedule')]
        ];
    }

    
}
