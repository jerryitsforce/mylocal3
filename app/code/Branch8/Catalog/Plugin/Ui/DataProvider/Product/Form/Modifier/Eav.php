<?php

declare(strict_types=1);

namespace Branch8\Catalog\Plugin\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Eav as BaseEav;

class Eav
{
    /**
     * Adjust container children.
     *
     * @param BaseEav $subject
     * @param array $result
     * @param array $attributeContainer
     * @param ProductAttributeInterface $attribute
     * @param string $groupCode
     * @param int $sortOrder
     *
     * @return array
     */
    public function afterAddContainerChildren(
        BaseEav                   $subject,
        array                     $result,
        array                     $attributeContainer,
        ProductAttributeInterface $attribute,
                                  $groupCode,
                                  $sortOrder
    ): array {
        $attributeCode = $attribute->getAttributeCode();
        $attributesNeedToCheck = [
            ProductAttributeInterface::CODE_PRICE,
            ProductAttributeInterface::CODE_SPECIAL_PRICE,
            ProductAttributeInterface::CODE_COST,
        ];
        if (in_array($attributeCode, $attributesNeedToCheck)) {
            if (isset($result['children'][$attributeCode]['arguments']['data']['config'])) {
                $result['children'][$attributeCode]['arguments']['data']['config']['validation']['validate-digits'] = true;
            }
        }

        return $result;
    }
}
