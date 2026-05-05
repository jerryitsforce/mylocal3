<?php
declare(strict_types=1);


namespace Branch8\MaskCustomerInformation\Model;

class RuleManagement
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
     * @param $code
     * @return RuleInterface
     */
    public function getRule($code)
    {
        return $this->rules[$code];
    }

    /**
     * @param $code
     * @return bool
     */
    public function hasRule($code)
    {
        return isset($this->rules[$code]);
    }
}
