<?php
namespace Branch8\GiftToFriend\Model\Config\Source;

class OrderType implements \Magento\Framework\Option\ArrayInterface
{

    const TYPE_REGULAR = 0;

    const TYPE_GIFT = 1;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::TYPE_REGULAR, 'label' => __('Regular Order')],
            ['value' => self::TYPE_GIFT, 'label' => __('Gift Order')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::TYPE_REGULAR => __('Regular Order'),
            self::TYPE_GIFT => __('Gift Order')
        ];
    }
}