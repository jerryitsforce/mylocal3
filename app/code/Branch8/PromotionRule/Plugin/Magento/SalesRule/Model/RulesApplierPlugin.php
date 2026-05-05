<?php

namespace Branch8\PromotionRule\Plugin\Magento\SalesRule\Model;

use Magento\Quote\Model\Quote\Item\AbstractItem;


class RulesApplierPlugin
{

    /**
     * @param $subject
     * @param $appliedRuleIds
     * @param AbstractItem $item
     * @param $rules
     * @param $skipValidation
     * @param array $couponCodes
     * @return mixed
     */
    public function afterApplyRules($subject, $appliedRuleIds, AbstractItem $item, $rules, $skipValidation, array $couponCodes = [])
    {
        /**
         * @var $discount \Magento\SalesRule\Model\Data\RuleDiscount
         */
        $extensions = $item->getExtensionAttributes();
        if ($extensions && $extensions->getDiscounts()) {
            $discounts = $extensions->getDiscounts();
            $item->setData('sale_rule_discount_breakdown', '');
            if($discounts) {
                $breakDown=[];
                foreach ($discounts as $discount) {
                    $breakDown[$discount->getRuleID()]=$discount->getDiscountData()->getAmount();
                }
                $item->setData('sale_rule_discount_breakdown', json_encode($breakDown));
            }
        }
        return $appliedRuleIds;
    }
}
