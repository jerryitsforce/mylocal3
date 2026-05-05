<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Product;

use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as OptionsFactory;
use Magento\ConfigurableProduct\Model\Product\VariationHandler;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class BuildConfigurableProduct
{
    /**#@+
     * Constants for keys of configurable product.
     */
    public const ATTRIBUTES = 'configurable_attributes';
    public const AFFECT_ATTRIBUTES = 'affect_configurable_product_attributes';
    public const ATTRIBUTES_DATA = 'configurable_attributes_data';
    public const ASSOCIATED_PRODUCT_IDS = 'configurable_associated_product_ids';
    public const VARIATIONS_MATRIX = 'configurable_variations_matrix';
    /**#@-*/

    /**
     * @var OptionsFactory
     */
    private OptionsFactory $optionsFactory;

    /**
     * @var VariationHandler
     */
    private VariationHandler $variationHandler;

    /**
     * @var ProductExtensionFactory
     */
    private ProductExtensionFactory $extensionAttributesFactory;

    /**
     * BuildConfigurableProduct constructor.
     *
     * @param OptionsFactory $optionsFactory
     * @param VariationHandler $variationHandler
     * @param ProductExtensionFactory $extensionAttributesFactory
     */
    public function __construct(
        OptionsFactory          $optionsFactory,
        VariationHandler        $variationHandler,
        ProductExtensionFactory $extensionAttributesFactory
    ) {
        $this->optionsFactory = $optionsFactory;
        $this->variationHandler = $variationHandler;
        $this->extensionAttributesFactory = $extensionAttributesFactory;
    }

    /**
     * Get the required keys for building a configurable product.
     *
     * @return string[]
     */
    public static function getRequiredKeys(): array
    {
        return [
            self::ATTRIBUTES,
            self::AFFECT_ATTRIBUTES,
            self::ATTRIBUTES_DATA,
            self::ASSOCIATED_PRODUCT_IDS,
            self::VARIATIONS_MATRIX
        ];
    }

    /**
     * Build configurable product.
     *
     * @param ProductInterface $product
     * @param array $data
     *
     * @return DataObject
     *
     * @throws LocalizedException
     */
    public function execute(ProductInterface $product, array $data): DataObject
    {
        $result = new DataObject();

        $setId = $data[ProductInterface::ATTRIBUTE_SET_ID] ?? null;
        if ($setId) {
            $product->setNewVariationsAttributeSetId($setId);
        }

        if (!empty($data[self::VARIATIONS_MATRIX])) {
            $generatedProductIds = $this->generateSimpleProducts($product, $data);
            $result->setData('ids', $generatedProductIds);
            $result->setData('status', false);
            return $result;
        }

        /** @var ProductExtensionInterface $extension */
        $extension = $product->getExtensionAttributes() ?? $this->extensionAttributesFactory->create();

        $attributes = $data[self::ATTRIBUTES] ?? [];

        if (!empty($data[self::ATTRIBUTES_DATA])) {
            $attributes = array_unique(array_merge($attributes, array_keys($data[self::ATTRIBUTES_DATA])));
            $configurableOptions = $this->optionsFactory->create($data[self::ATTRIBUTES_DATA]);
            $extension->setConfigurableProductOptions($configurableOptions);
        }
        $product->getTypeInstance()->setUsedProductAttributes($product, $attributes);

        $this->variationHandler->prepareAttributeSet($product);

        $associatedProductIds = $data[self::ASSOCIATED_PRODUCT_IDS] ?? [];
        $extension->setConfigurableProductLinks($associatedProductIds);

        $product->setCanSaveConfigurableAttributes((bool)($data[self::AFFECT_ATTRIBUTES] ?? 0));

        $product->setExtensionAttributes($extension);

        $result->setData('ids', $associatedProductIds);
        $result->setData('status', true);
        return $result;
    }

    /**
     * Generate simple products to link with configurable.
     *
     * @param ProductInterface $product
     * @param array $data
     *
     * @return array
     *
     * @throws LocalizedException
     */
    private function generateSimpleProducts(ProductInterface $product, array $data,): array
    {
        foreach (($data[self::VARIATIONS_MATRIX] ?? []) as &$value) {
            if (!empty($value['weight'])) {
                continue;
            }
            if (!empty($data['weight'])) {
                $value['weight'] = $data['weight'];
            } else {
                $value['product_has_weight'] = 0;
            }
            if (empty($value['quantity_and_stock_status']['qty'])) {
                $value['quantity_and_stock_status']['qty'] = 0;
            } elseif (empty($value['quantity_and_stock_status']['is_in_stock'])) {
                $value['quantity_and_stock_status']['is_in_stock'] = true;
            }
        }
        $variationsMatrix = $data[self::VARIATIONS_MATRIX] ?? [];
        return $this->variationHandler->generateSimpleProducts($product, $variationsMatrix);
    }
}
