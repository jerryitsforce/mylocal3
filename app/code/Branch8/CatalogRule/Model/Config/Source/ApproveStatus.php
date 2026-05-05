<?php
namespace Branch8\CatalogRule\Model\Config\Source;

class ApproveStatus implements \Magento\Framework\Option\ArrayInterface
{
    const STATUS_UNDER_REVIEW = 0;

    const STATUS_APPROVED = 1;

    const STATUS_REJECTED = 2;

    public function toOptionArray()
    {
        return [
            ['value' => self::STATUS_UNDER_REVIEW, 'label' => __('Pending Review')],
            ['value' => self::STATUS_APPROVED, 'label' => __('Approved')],
            ['value' => self::STATUS_REJECTED, 'label' => __('Rejected')]
        ];
    }

    
}
