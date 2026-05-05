<?php

declare(strict_types=1);

namespace Branch8\Catalog\Plugin\Ui\DataProvider\ProductSchedule\Form\Modifier;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\CatalogStaging\Ui\DataProvider\Product\Form\Modifier\Eav as BaseEav;

class Eav
{
    /**
     * Adjust pricing data.
     *
     * @param BaseEav $subject
     * @param array $result
     * @param array $data
     *
     * @return array
     */
    public function afterModifyData(BaseEav $subject, array $result, array $data): array
    {
        $attributesNeedToCheck = [
            ProductAttributeInterface::CODE_PRICE,
            ProductAttributeInterface::CODE_SPECIAL_PRICE,
            ProductAttributeInterface::CODE_COST
        ];

        foreach ($result as $key => &$modifyData) {
            if ($key === 'config' || !isset($modifyData['product'])) {
                continue;
            }
            foreach ($attributesNeedToCheck as $attributeCode) {
                if (!empty($modifyData['product'][$attributeCode])) {
                    $value = (float)$modifyData['product'][$attributeCode];
                    $modifyData['product'][$attributeCode] = round($value);
                }
            }
        }

        return $result;
    }
}
