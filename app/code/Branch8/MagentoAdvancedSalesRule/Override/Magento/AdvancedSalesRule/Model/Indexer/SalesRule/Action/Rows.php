<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\MagentoAdvancedSalesRule\Override\Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action;

use Branch8\MagentoAdvancedSalesRule\Model\GetFilterSKus;
use Magento\AdvancedRule\Model\Condition\Filter;
use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter as RuleFilter;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\SalesRule\Model\RuleFactory;

/**
 * Class Rows
 */
class Rows extends \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action\Rows
{

    private GetFilterSKus $getFilterSkus;

    /**
     * @param RuleFactory $ruleFactory
     * @param RuleFilter $filterResourceModel
     * @param GetFilterSKus $getFilterSkus
     */
    public function __construct(
        RuleFactory $ruleFactory,
        RuleFilter $filterResourceModel,
        GetFilterSKus $getFilterSkus,
    ) {
        $this->getFilterSkus = $getFilterSkus;
        $this->ruleFactory = $ruleFactory;
        $this->filterResourceModel = $filterResourceModel;
    }
    /**
     * @param SalesRule $rule
     * @return void
     */
    protected function saveFilters(SalesRule $rule)
    {
        $ruleId = $rule->getId();
        if ($ruleId) {
            $condition = $rule->getConditions();
            $isCouponCode = $rule->getCouponType() != SalesRule::COUPON_TYPE_NO_COUPON;
            $data = [];
            if ($condition instanceof FilterableConditionInterface && $condition->isFilterable()) {
                $filterGroups = $condition->getFilterGroups();
                $groupId = 1;
                foreach ($filterGroups as $filterGroup) {
                    $filters = $filterGroup->getFilters();
                    foreach ($filters as $filter) {
                        $filterSkus = $this->getFilterSkus->get($filter);
                        $data[] = [
                            'rule_id' => $ruleId,
                            'group_id' => $groupId,
                            'weight' => $filter->getWeight(),
                            Filter::KEY_FILTER_TEXT => $filter->getFilterText(),
                            'filter_text_hash' => hash('sha256', $filter->getFilterText()),
                            'filter_skus' => $filterSkus ? join(',', $filterSkus) : '',
                            Filter::KEY_FILTER_TEXT_GENERATOR_CLASS => $filter->getFilterTextGeneratorClass(),
                            Filter::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => $filter->getFilterTextGeneratorArguments(),
                            Filter::IS_COUPON => $isCouponCode,
                        ];
                    }
                    $groupId++;
                }
            }

            if (empty($data)) {
                $data = $this->getTruePlaceHolder($ruleId);
                $data[Filter::IS_COUPON] = $isCouponCode;
            }

            $this->filterResourceModel->insertFilters($data);
        }
    }
}
