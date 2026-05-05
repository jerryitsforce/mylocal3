<?php
declare(strict_types=1);

namespace Branch8\MaskInformation\Model\Rules;

use Branch8\MaskInformation\Api\MaskRuleActionInterface;
use Branch8\MaskInformation\Model\MaskSensitiveData;

/**
 *  OO安 = > OO安
 */
class GroupId implements MaskRuleActionInterface
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
        $length = strlen($inputValue);
        $firstOccur = MaskSensitiveData::getFirstOccurPositionOfChar($inputValue, '-');
        if ($firstOccur) {
            $begin = $firstOccur;
            $stop = $length - 1;
        } else {
            $begin = 0;
            $stop = $length - 1;
        }
        return MaskSensitiveData::mask((string)$inputValue, $begin, $stop, '*');
    }
}
