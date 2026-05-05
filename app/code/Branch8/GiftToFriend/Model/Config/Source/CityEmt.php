<?php
namespace Branch8\GiftToFriend\Model\Config\Source;

class CityEmt implements \Magento\Framework\Option\ArrayInterface
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
        return [];
    }

}