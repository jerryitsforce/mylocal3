<?php
declare(strict_types=1);

namespace Branch8\MaskInformation\Model;

use Branch8\MaskInformation\Api\MaskRuleActionInterface;
use Branch8\MaskInformation\Api\MaskRulesCompositeInterface;

/**
 * Mask Rule composites
 */
class MaskRulesComposite implements MaskRulesCompositeInterface
{
    private array $rules;

    /**
     * @param array $rules
     */
    public function __construct(
        array $rules = []
    )
    {
        $this->rules = $rules;
    }

    /**
     * Mask string by code
     * @param $code
     * @param $inputValue
     * @return string
     */
    public function mask($code, $inputValue): string
    {
        /**
         * @var $ruleAction MaskRuleActionInterface
         */
        if (isset($this->rules[$code])) {
            $ruleAction = $this->rules[$code];
            return $ruleAction->execute($inputValue);
        }
        return (string)$inputValue;
    }
}
