<?php
declare(strict_types=1);

namespace Branch8\MaskInformation\Model\Rules;

use Branch8\MaskInformation\Api\MaskRuleActionInterface;
use Branch8\MaskInformation\Model\MaskSensitiveData;

/**
 * s****@gmail .com
 */
class Email implements MaskRuleActionInterface
{
    /**
     * @param $inputValue
     * @return string
     */
    public function execute($inputValue): string
    {
        if (empty($inputValue)) {
            return '';
        }
        $pos = strpos((string)$inputValue, '@');
        if (!$pos) {
            return '*';
        }
        return MaskSensitiveData::mask(
            (string)$inputValue, 1, strlen(explode('@', (string)$inputValue)[0])
        );
    }

}
