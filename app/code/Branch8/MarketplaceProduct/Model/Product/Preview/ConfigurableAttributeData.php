<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Product\Preview;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\Data\AttributeOptionInterface;

class ConfigurableAttributeData
{
    /**
     * Get product attributes.
     *
     * @param Product $product
     * @param array $allowProducts
     *
     * @return array
     */
    public function getAttributesData(Product $product, array $allowProducts = []): array
    {
        $defaultValues = [];
        $attributes = [];

        $position = 0;
        $allowAttributes = $product->getTypeInstance()->getUsedProductAttributes($product) ?: [];
        /** @var ProductAttributeInterface $allowAttribute */
        foreach ($allowAttributes as $allowAttribute) {
            $attributeId = (int)$allowAttribute->getId();
            $attributeCode = $allowAttribute->getAttributeCode();

            $optionLabels = [];
            $attributeOptions = $allowAttribute->getOptions() ?: [];
            /** @var AttributeOptionInterface $attributeOption */
            foreach ($attributeOptions as $attributeOption) {
                if (!$attributeOption->getValue()) {
                    continue;
                }
                $optionLabels[$attributeOption->getValue()] = $attributeOption->getLabel();
            }
            $attributeOptionsData = $this->getAttributeOptionsData($attributeCode, $allowProducts, $optionLabels);
            if ($attributeOptionsData) {
                $attributes[$attributeId] = [
                    'id' => $attributeId,
                    'code' => $allowAttribute->getAttributeCode(),
                    'label' => $allowAttribute->getStoreLabel($product->getStoreId()),
                    'options' => $attributeOptionsData,
                    'position' => $position++
                ];
                $defaultValues[$attributeId] = $this->getAttributeConfigValue($attributeId, $product);
            }
        }
        return [
            'attributes' => $attributes,
            'defaultValues' => $defaultValues,
        ];
    }

    /**
     * Retrieve attribute options data.
     *
     * @param string $attributeCode
     * @param array $allowProducts
     * @param array $optionLabels
     *
     * @return array
     */
    private function getAttributeOptionsData(string $attributeCode, array $allowProducts, array $optionLabels): array
    {
        $attributeOptionsData = [];

        /** @var Product $allowProduct */
        foreach ($allowProducts as $allowProduct) {
            $optionId = $allowProduct->getData($attributeCode);

            if (isset($attributeOptionsData[$optionId]['id']) && $attributeOptionsData[$optionId]['id'] == $optionId) {
                $attributeOptionsData[$optionId]['products'][] = $allowProduct->getId();
            } else {
                $attributeOptionsData[$optionId] = [
                    'id' => $optionId,
                    'label' => $optionLabels[$optionId] ?? null,
                    'products' => [$allowProduct->getId()],
                ];
            }

        }
        return $attributeOptionsData;
    }

    /**
     * Retrieve attribute config value.
     *
     * @param int $attributeId
     *
     * @param Product $product
     *
     * @return mixed|null
     */
    protected function getAttributeConfigValue(int $attributeId, Product $product): mixed
    {
        return $product->hasPreconfiguredValues()
            ? $product->getPreconfiguredValues()->getData('super_attribute/' . $attributeId)
            : null;
    }
}
