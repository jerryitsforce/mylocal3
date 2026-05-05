<?php
declare(strict_types=1);

namespace Branch8\MaskInformation\Model\Rules;

use Branch8\MaskInformation\Api\MaskRuleActionInterface;
use Branch8\MaskInformation\Model\MaskSensitiveData;


class BirthDay implements MaskRuleActionInterface
{
    /**
     * @param $inputValue
     * @return string
     */
    public function execute($inputValue): string
    {
        return MaskSensitiveData::mask(
            MaskSensitiveData::mask(
                MaskSensitiveData::mask((string)$inputValue, 3, 1), 5, 2),
            8, 1
        );
    }

}
