<?php

namespace Branch8\EventTicket\Model\Config\Source;

class DisplayType implements \Magento\Framework\Option\ArrayInterface
{
    const TYPE_BARCODE = 1;

    const TYPE_NUMBER = 2;

    const TYPE_BOTH = 3;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::TYPE_BARCODE, 'label' => __('Barcode')],
            ['value' => self::TYPE_NUMBER, 'label' => __('Number')],
            ['value' => self::TYPE_BOTH, 'label' => 'Both Number and Barcode']
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
            self::TYPE_BARCODE => __('Barcode'),
            self::TYPE_NUMBER => __('Number'),
            self::TYPE_NUMBER => __('Both Number and Barcode')
        ];
    }

}