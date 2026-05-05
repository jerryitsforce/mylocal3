<?php

namespace Branch8\PromotionRule\Plugin\Magento\Quote\Model\Quote\Item;

class ItemPlugin
{
    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $subject
     * @param $result
     * @return mixed
     */
    public function afterSetAppliedRuleIds(\Magento\Quote\Model\Quote\Item\AbstractItem $subject, $result)
    {
        if (!$subject->getData('applied_rule_ids')) {
            $subject->setData('applied_rule_names', '');
        }
        return $result;
    }
}
