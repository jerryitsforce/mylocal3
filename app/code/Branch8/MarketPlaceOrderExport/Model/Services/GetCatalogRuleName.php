<?php

namespace Branch8\MarketPlaceOrderExport\Model\Services;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManager;
use  Branch8\HifiSalesReport\Helper\Common as CommonHelper;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

class GetCatalogRuleName
{
    private $cached = [];
    private RuleCollectionFactory $ruleCollectionFactory;

    /**
     * @param RuleCollectionFactory $ruleCollectionFactory
     */
    public function __construct(
        RuleCollectionFactory $ruleCollectionFactory,
    )
    {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
    }

    /**
     * @param $ruleId
     * @return mixed|string
     */
    public function get($ruleId)
    {
        if (isset($this->cached[$ruleId])) {
            return $this->cached[$ruleId];
        } elseif (!empty($this->cached)) {
            return '';
        }
        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->addFieldToSelect(['rule_id', 'name']);
        if ($ruleCollection->count()) {
            foreach ($ruleCollection as $rule) {
                $this->cached[$rule->getId()] = $rule->getName();
            }
        }
        return $this->cached[$ruleId] ?? '';
    }
}
