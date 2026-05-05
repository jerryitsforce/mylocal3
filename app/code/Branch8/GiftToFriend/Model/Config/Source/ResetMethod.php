<?php
namespace Branch8\GiftToFriend\Model\Config\Source;

class ResetMethod implements \Magento\Framework\Option\ArrayInterface
{
    const OPTION_BY_IP_AND_CODE = 1;
    const OPTION_BY_IP_OR_CODE = 2;
    const OPTION_BY_IP = 2;
    const OPTION_BY_CODE = 3;
    const OPTION_NONE = 0;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::OPTION_BY_IP_AND_CODE, 'label' => __('By IP and CODE')],
            ['value' => self::OPTION_BY_IP_OR_CODE, 'label' => __('By IP or CODE')],
            ['value' => self::OPTION_BY_IP, 'label' => __('By IP')],
            ['value' => self::OPTION_BY_CODE, 'label' => __('By CODE')],
            ['value' => self::OPTION_NONE, 'label' => __('None')],
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
            self::OPTION_BY_IP_AND_CODE => __('By IP and CODE'),
            self::OPTION_BY_IP_OR_CODE => __('By IP or CODE'),
            self::OPTION_BY_IP => __('By IP'),
            self::OPTION_BY_CODE => __('By CODE'),
            self::OPTION_NONE => __('None'),
        ];
    }
}