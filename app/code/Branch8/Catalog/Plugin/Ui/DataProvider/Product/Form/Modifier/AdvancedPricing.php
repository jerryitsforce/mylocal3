<?php

declare(strict_types=1);

namespace Branch8\Catalog\Plugin\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AdvancedPricing as BaseAdvancedPricing;

class AdvancedPricing
{
    /**
     * Adjust modify meta.
     *
     * @param BaseAdvancedPricing $subject
     * @param array $result
     * @param array $meta
     *
     * @return array
     */
    public function afterModifyMeta(BaseAdvancedPricing $subject, array $result, array $meta): array
    {
        if (isset($result['advanced_pricing_modal'])) {
            if (isset($result['advanced_pricing_modal']['children']['advanced-pricing']['children']['container_msrp']
                ['children']['msrp']['arguments']['data']['config'])) {
                $result['advanced_pricing_modal']['children']['advanced-pricing']['children']['container_msrp']['children']
                ['msrp']['arguments']['data']['config']['validation']['validate-digits'] = true;
            }

            if (isset($result['advanced_pricing_modal']['children']['advanced-pricing']['children']['tier_price']['children']['record']
                ['children']['price']['arguments']['data']['config'])) {
                $result['advanced_pricing_modal']['children']['advanced-pricing']['children']['tier_price']['children']['record']
                ['children']['price']['arguments']['data']['config']['validation']['validate-digits']= true;
            }
        }

        return $result;
    }
}
