<?php
declare(strict_types=1);

namespace Branch8\MaskInformation\Model\Rules;

use Branch8\MaskInformation\Api\MaskRuleActionInterface;
use Branch8\MaskInformation\Model\MaskSensitiveData;

/**
 *  OO安 = > OO安
 */
class FirstName implements MaskRuleActionInterface
{
    /**
     * @param $inputValue
     * @return string
     */
    public function execute($inputValue): string
    {
        return MaskSensitiveData::mask((string)$inputValue, 0, 2, 'O');
    }

}
