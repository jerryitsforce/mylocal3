<?php

namespace Branch8\MagentoAdvancedSalesRule\Model;

use Magento\AdvancedRule\Model\Condition\Filter;

class GetFilterSKus
{
    const PREFIX = 'product:attribute:sku:';

    /**
     * @param Filter $filter
     * @return array|string[]
     */
    public function get(Filter $filter)
    {
        $skus = [];
        $prefix = self::PREFIX;
        if ($filter->getFilterTextGeneratorClass() === "Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Attribute"
            && (str_contains($filter->getFilterText(), $prefix))
        ) {
            $skuString = str_replace($prefix, "", $filter->getFilterText());
            $skus = explode(",", $skuString);
        }
        return $skus;
    }
}
