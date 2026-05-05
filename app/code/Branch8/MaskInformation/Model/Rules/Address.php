<?php
declare(strict_types=1);

namespace Branch8\MaskInformation\Model\Rules;

use Branch8\MaskInformation\Api\MaskRuleActionInterface;
use Branch8\MaskInformation\Model\MaskSensitiveData;

/**
 * 12th Floor, ********** Street, Zhongshan District, Taipei City
 */
class Address implements MaskRuleActionInterface
{
    /**
     * @param $inputValue
     * @return string
     */
    public function execute($inputValue): string
    {
        if(!$inputValue){
            return '';
        }
        $defaultLength = 5;
        $firstOccur = MaskSensitiveData::getFirstOccurPositionOfChar($inputValue);
        $secondOccur = MaskSensitiveData::getSecondOccurPositionOfChar($inputValue);
        $start = $firstOccur !== -1 ? $firstOccur : 0;
        $length = $secondOccur !== -1 ? $secondOccur - $firstOccur : $defaultLength;
        return MaskSensitiveData::mask((string)$inputValue, $start, $length);
    }

}
