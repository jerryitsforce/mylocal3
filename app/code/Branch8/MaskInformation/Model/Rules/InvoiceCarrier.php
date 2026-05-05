<?php
declare(strict_types=1);

namespace Branch8\MaskInformation\Model\Rules;

use Branch8\MaskInformation\Api\MaskRuleActionInterface;
use Branch8\MaskInformation\Model\MaskSensitiveData;

/**
 *  OO安 = > OO安
 */
class InvoiceCarrier implements MaskRuleActionInterface
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
        if ($length === 1) {
            $stop = 1;
        } else {
            $stop = $length - 1;
        }
        return MaskSensitiveData::mask((string)$inputValue, 0, $stop, '*');
    }
}
