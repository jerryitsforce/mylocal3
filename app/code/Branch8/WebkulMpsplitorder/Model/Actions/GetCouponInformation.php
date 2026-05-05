<?php

namespace Branch8\WebkulMpsplitorder\Model\Actions;

use Amasty\Rules\Helper\Data;

class GetCouponInformation
{
    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_ruleFactory;

    /**
     * @var \Magento\SalesRule\Model\Coupon
     */
    protected $_coupon;

    private $cached = [];

    private $ruleActions = [];

    private \Magento\SalesRule\Model\RuleRepository $ruleRepository;
    private \Magento\Framework\Module\Manager $moduleManager;

    /**
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\SalesRule\Model\Coupon\Proxy $coupon
     * @param \Magento\SalesRule\Model\RuleRepository $ruleRepository
     * @param \Magento\Framework\Module\Manager $moduleManager
     */
    public function __construct(
        \Magento\SalesRule\Model\RuleFactory    $ruleFactory,
        \Magento\SalesRule\Model\Coupon\Proxy   $coupon,
        \Magento\SalesRule\Model\RuleRepository $ruleRepository,
        \Magento\Framework\Module\Manager       $moduleManager
    )
    {
        $this->_ruleFactory = $ruleFactory;
        $this->_coupon = $coupon;
        $this->ruleRepository = $ruleRepository;
        $this->moduleManager = $moduleManager;
        $this->initRuleActions();
    }

    /**
     * @return array
     */
    private function initRuleActions()
    {
        $this->ruleActions = [
            'by_percent' => __('Percent of product price discount'),
            'by_fixed' => __('Fixed amount discount'),
            'cart_fixed' => __('Fixed amount discount for whole cart'),
            'buy_x_get_y' => __('Buy N products, and get next products with discount')
        ];
        $amastyPromoOptions = [
            'ampromo_items' => __('Auto add promo items with products'),
            'ampromo_cart' => __('Auto add promo items for the whole cart'),
            'ampromo_product' => __('Auto add the same product'),
            'ampromo_spent' => __('Auto add promo items for every $X spent')
        ];
        $this->ruleActions = array_merge($this->ruleActions, $amastyPromoOptions);
        $this->ruleActions = array_merge($this->ruleActions, Data::staticGetDiscountTypes());
        return $this->ruleActions;
    }

    /**
     * @return string[]
     */
    public function get($couponCode)
    {
        if (isset($this->cached[$couponCode])) {
            return $this->cached[$couponCode];
        }
        $this->_coupon->loadByCode($couponCode);
        $ruleId = $this->_coupon->getRuleId();
        $data = [
            'coupon_code' => $couponCode,
            'coupon_price_rule' => '',
            'coupon_rule_name' => '',
            'coupon_rule_id' => 0
        ];
        $this->cached[$couponCode] = $data;
        if (!empty($ruleId) && ($rule = $this->getRule($ruleId))) {
            $this->cached[$couponCode]['coupon_rule_name'] = $rule->getName();
            $this->cached[$couponCode]['coupon_price_rule'] = isset($this->ruleActions[$rule->getSimpleAction()]) ? $this->ruleActions[$rule->getSimpleAction()] : '';
            $this->cached[$couponCode]['coupon_rule_id'] = $ruleId;
        }
        return $this->cached[$couponCode];
    }

    /**
     * @param $ruleId
     * @return \Magento\SalesRule\Api\Data\RuleInterface|\Magento\SalesRule\Model\Data\Rule|null
     */
    private function getRule($ruleId)
    {
        try {
            return $this->ruleRepository->getById((int)$ruleId);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getRuleActions($r)
    {

    }
}
