<?php

namespace Branch8\Customer\Helper;

class AddressRenderer
{
    const MASKED_STRING = '**********';

    public function renderHomeAddress($address)
    {
        $dataAddress = [];
        $streetLines = $address->getStreet();
        if (count($streetLines) > 1) {
            $street1 = $streetLines[1];
            $street1Array = explode(' ', $street1);
            $lastWord = count($street1Array) > 1 ? array_pop($street1Array) : '';

            $dataAddress['street'] = $streetLines[0] . ', '. self::MASKED_STRING . ' ' . $lastWord;
        } else {
            $streetArray = explode(' ', $address->getStreetFull() ?? '');
            $lastWord = count($streetArray) > 1 ? array_pop($streetArray) : '';
            $dataAddress['street'] = self::MASKED_STRING . ' ' . $lastWord;
        }

        if ($address->getRegionId()) {
            $dataAddress['region'] = $address->getRegion();
        }

        if ($address->getCity()) {
            $dataAddress['city'] = $address->getCity();
        }

        return implode(', ', $dataAddress);
    }
}
