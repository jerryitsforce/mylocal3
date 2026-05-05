<?php
namespace Branch8\GiftToFriend\Model\Config\Source;

class AddressType implements \Magento\Framework\Option\ArrayInterface
{

    const BUYER_INPUT_ADDRESS = 1;

    const RECIPIENT_INPUT_ADDRESS = 2;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::BUYER_INPUT_ADDRESS, 'label' => __('Giver')],
            ['value' => self::RECIPIENT_INPUT_ADDRESS, 'label' => __('Recipient')]
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
            self::BUYER_INPUT_ADDRESS => __('Buyer'),
            self::RECIPIENT_INPUT_ADDRESS => __('Recipient')
        ];
    }
}