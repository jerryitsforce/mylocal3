<?php
namespace Branch8\MarketplaceStaging\Model\Product\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class CreatedFrom is used tp get the product type options
 */
class CreatedFrom implements OptionSourceInterface
{
    public const CREATED_FROM_SELLER = 0;
    public const CREATED_FROM_SCHEDULE = 1;
    public const CREATED_FROM_IMPORTED = 2;
    public const CREATED_FROM_SCHEDULE_IMPORTED = 3;

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::CREATED_FROM_SELLER, 'label' => __('Seller')],
            ['value' => self::CREATED_FROM_SCHEDULE, 'label' => __('Schedule')],
            ['value' => self::CREATED_FROM_IMPORTED, 'label' => __('Import')],
            ['value' => self::CREATED_FROM_SCHEDULE_IMPORTED, 'label' => __('Schedule Import')]
        ];
    }
}
