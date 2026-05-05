<?php
declare(strict_types=1);


namespace Branch8\MaskCustomerInformation\Model\CreditNemo;

use Branch8\MaskCustomerInformation\Model\RuleInterface;
use Branch8\MaskInformation\Model\MaskRulesComposite;

class Rule implements RuleInterface
{
    private RuleConfig $ruleConfig;
    private MaskRulesComposite $rulesComposite;

    /**
     * @param RuleConfig $ruleConfig
     * @param MaskRulesComposite $rulesComposite
     */
    public function __construct(
        RuleConfig         $ruleConfig,
        MaskRulesComposite $rulesComposite
    )
    {
        $this->ruleConfig = $ruleConfig;
        $this->rulesComposite = $rulesComposite;
    }

    /**
     * @param array $data
     * @return array
     */
    public function apply(array $data): array
    {
        $rules = $this->ruleConfig->getRules();
        foreach ($data as $field => &$value) {
            if (empty($rules[$field])) {
                continue;
            }
            $rule = $rules[$field];
            if ($field === 'street' && is_array($value)) {
                $value[0] = $this->rulesComposite->mask($rule, $value[0]);
                $data[$field] = $value;
            } else {
                $data[$field] = $this->rulesComposite->mask($rule, $value);
            }
        }
        return $data;
    }

    /**
     * @return string[]
     */
    public function getRuleConfig(): array
    {
        return $this->ruleConfig->getRules();
    }
}
