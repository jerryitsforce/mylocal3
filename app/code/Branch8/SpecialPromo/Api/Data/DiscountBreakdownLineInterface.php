<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Special Promotions Base for Magento 2
 */

namespace Branch8\SpecialPromo\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface DiscountBreakdownLineInterface extends ExtensibleDataInterface
{
    /**
     * Constant used as key into $_data
     */
    public const RULE_NAME = 'rule_name';
    public const RULE_AMOUNT = 'rule_amount';
    public const IS_COUPON_DISCOUNT = 'is_coupon_discount';

    /**
     * @return string|null
     */
    public function getRuleName();

    /**
     * @param string $ruleName
     * @return $this
     */
    public function setRuleName($ruleName);

    /**
     * @return string
     */
    public function getRuleAmount();

    /**
     * @param string $ruleAmount
     * @return $this
     */
    public function setRuleAmount($ruleAmount);

    /**
     * @param bool $isCouponDiscount
     * @return $this
     */
    public function setIsCouponDiscount($isCouponDiscount);

    /**
     * @return bool
     */
    public function getIsCouponDiscount();
}
