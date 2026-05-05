<?php
declare(strict_types=1);

namespace Branch8\MaskInformation\Model\Rules;

use Branch8\MaskInformation\Api\MaskRuleActionInterface;
use Branch8\MaskInformation\Model\MaskSensitiveData;


class BirthDayEn implements MaskRuleActionInterface
{
    /**
     * @param $inputValue
     * @return string
     */
    public function execute($inputValue): string
    {
        return MaskSensitiveData::mask(
            MaskSensitiveData::mask(
                MaskSensitiveData::mask((string)$inputValue, 1, 1), 3, 2),
            6, 1
        );
    }

}
