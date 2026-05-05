<?php
declare(strict_types=1);

namespace Branch8\MaskInformation\Model\Rules;

use Branch8\MaskInformation\Api\MaskRuleActionInterface;
use Branch8\MaskInformation\Model\MaskSensitiveData;

/**
 *  OO安 = > OO安
 */
class CustomerGroup implements MaskRuleActionInterface
{
    /**
     * @param $inputValue
     * @return string
     */
    public function execute($inputValue): string
    {
        if((string)$inputValue===''){
            return '';
        }
        return MaskSensitiveData::mask((string)$inputValue, 1, 5, '*');
    }

}
