<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\ViewModel;

use Branch8\MaskCustomerInformation\Model\RuleManagement;
use Branch8\MaskInformation\Api\MaskRulesCompositeInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 *
 */
class MaskPersonal implements ArgumentInterface
{
    /**
     * @var MaskRulesCompositeInterface
     */
    private $ruleManagement;
    private MaskRulesCompositeInterface $maskRuleComposite;

    /**
     * @param MaskRulesCompositeInterface $maskRulesComposite
     * @param RuleManagement $ruleManagement
     */
    public function __construct(
        MaskRulesCompositeInterface $maskRulesComposite,
        RuleManagement              $ruleManagement
    )
    {
        $this->ruleManagement = $ruleManagement;
        $this->maskRuleComposite = $maskRulesComposite;
    }

    /**
     * @param $code
     * @param $data
     * @return array
     */
    public function mask($code, $data)
    {
        return $this->ruleManagement->getRule($code)->apply($data);
    }

    /**
     * @param $code
     * @param $value
     * @return string
     */
    public function maskByCode($code, $value)
    {
        return $this->maskRuleComposite->mask($code,$value);
    }
}
