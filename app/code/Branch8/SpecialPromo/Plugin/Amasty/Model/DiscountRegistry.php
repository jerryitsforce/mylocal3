<?php

namespace Branch8\SpecialPromo\Plugin\Amasty\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class DiscountRegistry
{
    protected $ruleRepository;

    protected $breakdownLineFactory;

    protected $storeManager;

    private $logger;

    public function __construct(
        \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository,
//        DiscountBreakdownLineFactory $breakdownLineFactory,
        \Branch8\SpecialPromo\Model\DiscountBreakdownLineFactory $discountBreakdownLineFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->ruleRepository = $ruleRepository;
//        $this->breakdownLineFactory = $breakdownLineFactory;
        $this->breakdownLineFactory = $discountBreakdownLineFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }
    public function aroundGetRulesWithAmount($subject, $process)
    {
        $totalAmount = [];
        $shippingDiscountDataForBreakdown = $subject->getShippingDiscountDataForBreakdown();

        try {
            foreach ($subject->getDiscount() as $ruleId => $ruleItemsAmount) {
                /** @var \Magento\SalesRule\Api\Data\RuleInterface $rule */
                $rule = $this->ruleRepository->getById($ruleId);
                $ruleAmount = array_sum($ruleItemsAmount);

                if (isset($shippingDiscountDataForBreakdown[$ruleId])) {
                    $ruleAmount += $shippingDiscountDataForBreakdown[$ruleId];
                }
                $breakdownLine = $this->breakdownLineFactory->create();
                if($rule->getCouponType() == \Magento\SalesRule\Api\Data\RuleInterface::COUPON_TYPE_SPECIFIC_COUPON){
                    $breakdownLine->setIsCouponDiscount(true);
                }else{
                    $breakdownLine->setIsCouponDiscount(false);
                }
                if ($ruleAmount > 0) {
                    if ($this->getRuleStoreLabel($rule)) {
                        $breakdownLine->setRuleName($this->getRuleStoreLabel($rule));
                    } else {
                        $breakdownLine->setRuleName($rule->getName());
                    }

                    $ruleAmount = $this->storeManager->getStore()->getCurrentCurrency()->format($ruleAmount, [], false);
                    $breakdownLine->setRuleAmount('-' . $ruleAmount);

                    $totalAmount[] = $breakdownLine;
                }else if($ruleAmount == 0){
                    if ($this->getRuleStoreLabel($rule)) {
                        $breakdownLine->setRuleName($this->getRuleStoreLabel($rule));
                    } else {
                        $breakdownLine->setRuleName($rule->getName());
                    }
                    $ruleAmount = '';
                    $breakdownLine->setRuleAmount($ruleAmount);
                    $totalAmount[] = $breakdownLine;
                }
            }
        } catch (NoSuchEntityException $entityException) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_SpecialPromo', 'exceptionlog')){
                $this->logger->critical($entityException);
            }
        } catch (LocalizedException $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_SpecialPromo', 'exceptionlog')){
                $this->logger->critical($e);
            }
        }

        return $totalAmount;

    }

    private function getRuleStoreLabel($rule)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $storeLabel = $storeLabelDefault = null;

        /* @var $label \Magento\SalesRule\Model\Data\RuleLabel */
        foreach ($rule->getStoreLabels() as $label) {
            if ($label->getStoreId() === 0) {
                $storeLabelDefault = $label->getStoreLabel();
            }

            if ($label->getStoreId() == $storeId) {
                $storeLabel = $label->getStoreLabel();
                break;
            }
        }

        $storeLabel = $storeLabel ?: $storeLabelDefault;

        return $storeLabel;
    }

}