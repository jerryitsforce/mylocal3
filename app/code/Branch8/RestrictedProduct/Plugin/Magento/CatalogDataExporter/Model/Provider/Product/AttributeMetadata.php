<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\RestrictedProduct\Plugin\Magento\CatalogDataExporter\Model\Provider\Product;

class AttributeMetadata
{

    public function aroundGetAttributeValue(
        \Magento\CatalogDataExporter\Model\Provider\Product\AttributeMetadata $subject,
        \Closure $proceed,
        string $attributeCode,
        string $storeViewCode,
        string $value
    ) {
        if($attributeCode == 'allow_customer_groups' || 
            $attributeCode == 'shipping_method')
        {
            return explode(',', $value);
        }
        $result = $proceed($attributeCode, $storeViewCode, $value);
        return $result;
    }
}