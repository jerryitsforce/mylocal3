<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Special Promotions Pro for Magento 2
 */

namespace Branch8\Sales\Plugin\SalesRule\Model\Rule\Metadata;

use Magento\SalesRule\Model\Rule\Metadata\ValueProvider as SalesRuleValueProvider;

/**
 * Add Amasty Rule tooltip to default field.
 */
class ValueProvider
{
    /**
     * @param SalesRuleValueProvider $subject
     * @param array $result
     * @return mixed
     */
    public function afterGetMetadataValues(
        SalesRuleValueProvider $subject,
        $result
    ) {
        $result['actions']['children']['discount_qty']['arguments']['data']['config']['tooltip']['description'] =
            __('The maximum number of times the discount can be applied. Examples: •if your promotion is set to apply a discount on each second product in the cart and you set this to \'2\' the discount will be applied only twice even if a customer has 5 or more items in the cart. •if you want to apply a discount on the only product (Action = The Most Expensive/The Cheapest, also Buy 1 get 1 free) set the value to \'1\'.');

        return $result;
    }
}
