<?php
declare(strict_types=1);

namespace Branch8\MaskInformation\Model\Rules;

use Branch8\MaskInformation\Api\MaskRuleActionInterface;
use Branch8\MaskInformation\Model\MaskSensitiveData;

/**
 *  ex :0928513252 = > *98****72
 */
class Phone implements MaskRuleActionInterface
{
    /**
     * @param $inputValue
     * @return string
     */
    public function execute($inputValue): string
    {
        if (!$inputValue) {
            return '';
        }
        // taiwan phone 10 char
        $length = strlen($inputValue);
        return MaskSensitiveData::mask((string)$inputValue, 3, 5, '*');
    }

}
