<?php

namespace Branch8\SpecialPromo\Model;

use Branch8\SpecialPromo\Api\Data\DiscountBreakdownLineInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class DiscountBreakdownLine extends AbstractSimpleObject implements DiscountBreakdownLineInterface
{
    /**
     * @return string
     */
    public function getRuleName()
    {
        return $this->_get(self::RULE_NAME);
    }

    /**
     * @param string $ruleName
     * @return $this
     */
    public function setRuleName($ruleName)
    {
        $this->setData(self::RULE_NAME, $ruleName);
        return $this;
    }

    /**
     * @return string
     */
    public function getRuleAmount()
    {
        return $this->_get(self::RULE_AMOUNT);
    }

    /**
     * @param string $ruleAmount
     * @return $this
     */
    public function setRuleAmount($ruleAmount)
    {
        $this->setData(self::RULE_AMOUNT, $ruleAmount);
        return $this;
    }

    /**
     * @param bool $isCouponDiscount
     * @return $this
     */
    public function setIsCouponDiscount($isCouponDiscount)
    {
        $this->setData(self::IS_COUPON_DISCOUNT, $isCouponDiscount);
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsCouponDiscount()
    {
        return $this->_get(self::IS_COUPON_DISCOUNT);
    }
}
