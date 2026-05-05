<?php

declare(strict_types=1);

namespace Branch8\Quote\Plugin\RulesApplier;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Model\RulesApplier;

class SetAppliedRuleNames
{
    /**
     * @var RuleRepositoryInterface
     */
    private RuleRepositoryInterface $ruleRepository;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    public $logger;


    /**
     * SetAppliedRuleNames constructor.
     *
     * @param RuleRepositoryInterface $ruleRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        RuleRepositoryInterface $ruleRepository,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->ruleRepository = $ruleRepository;
        $this->logger = $logger;
    }

    /**
     * Sets applied rule names.
     *
     * @param RulesApplier $subject
     * @param RulesApplier $result
     * @param AbstractItem $item
     * @param array $appliedRuleIds
     *
     * @return RulesApplier
     */
    public function afterSetAppliedRuleIds(
        RulesApplier $subject,
        RulesApplier $result,
        AbstractItem $item,
        array        $appliedRuleIds
    ): RulesApplier {
        $ruleIds = $item->getAppliedRuleIds();

        if (empty($ruleIds)) {
            $item->setData('applied_rule_names','');
            return $result;
        }

        if (!is_array($ruleIds)) {
            $ruleIds = explode(',', $ruleIds);
        }

        $ruleNames = [];
        foreach ($ruleIds as $ruleId) {
            try {
                $rule = $this->ruleRepository->getById($ruleId);
                $ruleNames[] = $rule->getName();
                // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
            } catch (\Exception $e) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Quote', 'exceptionlog')){
                    $this->logger->critical($e);
                }
            }
        }
        $item->setData('applied_rule_names', implode(',', $ruleNames));

        return $result;
    }
}
