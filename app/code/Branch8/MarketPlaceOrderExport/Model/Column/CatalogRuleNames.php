<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\GetCatalogRuleName;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

class CatalogRuleNames implements ColumnInterface
{
    private GetCatalogRuleName $getCatalogRuleName;

    /**
     * @param GetCatalogRuleName $getCatalogRuleName
     */
    public function __construct(
        GetCatalogRuleName $getCatalogRuleName
    )
    {
        $this->getCatalogRuleName = $getCatalogRuleName;
    }

    public function getHeader()
    {
        return __('Catalog Rule Name');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if ($row['item_type'] === 'shipping') {
            return '';
        }

        if ($row['invoice_order_item_name'] === '訂單處理費') {
            return '';
        }
        if (empty($row["price_log"])) {
            return '';
        }
        $priceLog = $row["price_log"];
        if (empty($priceLog)) {
            return '';
        }
        $appliedRuleNamesArray = [];
        $appiledRuleData = json_decode($priceLog, true);
        if (empty($appiledRuleData)) {
            return implode(",", $appliedRuleNamesArray);
        }
        foreach ($appiledRuleData as $rule) {
            if (!isset($rule["rule_id"])) {
                $appliedRuleNamesArray[] = "找不到rule_id欄位";
                continue;
            }

            $ruleName = $this->getCatalogRuleName->get($rule["rule_id"]);

            if (!($ruleName)) {
                $appliedRuleNamesArray[] = "找不到對應rule名稱(rule_id: {$rule["rule_id"]})";
                continue;
            }

            $appliedRuleNamesArray[] = $ruleName;
        }
        return implode(",", $appliedRuleNamesArray);
    }
}
