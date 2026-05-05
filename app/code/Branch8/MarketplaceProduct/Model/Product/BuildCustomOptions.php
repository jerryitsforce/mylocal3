<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Product;

use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface;

class BuildCustomOptions
{
    /**#@+
     * Constants for keys of product custom options.
     */
    public const KEY_CUSTOM_OPTIONS = 'options';
    /**#@-*/

    /**
     * @var ProductCustomOptionInterfaceFactory
     */
    protected ProductCustomOptionInterfaceFactory $productCustomOption;

    /**
     * @var ProductCustomOptionValuesInterfaceFactory
     */
    protected ProductCustomOptionValuesInterfaceFactory $productCustomOptionValues;

    /**
     * BuildCustomOptions constructor.
     *
     * @param ProductCustomOptionInterfaceFactory $productCustomOption
     * @param ProductCustomOptionValuesInterfaceFactory $productCustomOptionValues
     */
    public function __construct(
        ProductCustomOptionInterfaceFactory $productCustomOption,
        ProductCustomOptionValuesInterfaceFactory $productCustomOptionValues
    ){
        $this->productCustomOption = $productCustomOption;
        $this->productCustomOptionValues = $productCustomOptionValues;
    }

    /**
     * Build product custom options to product.
     *
     * @param ProductInterface $product
     * @param array $productOptions
     * @param bool $previewFlag
     * @return void
     */
    public function execute(ProductInterface $product, array $productOptions, $previewFlag = false): void
    {
        $draftContent = $product->getData('draft_content') ?? false;
        if ($productOptions && !$product->getOptionsReadonly()) {
            // mark custom options that should to fall back to default value
            $options = $this->mergeProductOptions(
                $productOptions,
                []
            );
            $originalProductOptions = $this->getOriginalProductOptions($product);


            $customOptions = [];
            $i = 1;
            foreach ($options as $customOptionData) {
                $isNewOption = false;
                if (empty($customOptionData['is_delete'])) {
                    if (empty($customOptionData['option_id'])) {
                        if ($previewFlag) {
                            $customOptionData['option_id'] = $i;
                        } else {
                            $customOptionData['option_id'] = null;
                        }
                    } else {
                        if (empty($originalProductOptions)) {
                            $customOptionData['option_id'] = null;
                            $isNewOption = true;
                        } else {
                            $isNewOption = !in_array($customOptionData['option_id'], array_keys($originalProductOptions));
                            $customOptionData['option_id'] =  !$isNewOption ? $customOptionData['option_id'] : null;
                        }
                    }
                    if (isset($customOptionData['values'])) {
                        $customOptionData['values'] = array_filter(
                            $customOptionData['values'],
                            function ($valueData) {
                                return empty($valueData['is_delete']);
                            }
                        );

                        $optType = $customOptionData['type'];
                        $j = $i * 100 + 1;
                        if ($optType == "drop-down" || $optType == "drop_down" || $optType == "radio") {
                            foreach($customOptionData['values'] as $key => $value){
                                if(!array_key_exists('is_visible', $value)){
                                    $customOptionData['values'][$key]['is_visible'] = 0;
                                }
                                if (empty($value['value_id'])) {
                                    if ($previewFlag) {
                                        $customOptionData['values'][$key]['value_id'] = $j++;
                                    }
                                }
                                if ($previewFlag) {
                                    $customOptionData['values'][$key] = $this->productCustomOptionValues->create(['data' => $customOptionData['values'][$key]])->setProduct($product);
                                }
                                if (!$isNewOption) {
                                    if (isset($customOptionData['values'][$key]['option_type_id'])
                                        && !in_array($customOptionData['values'][$key]['option_type_id'], $originalProductOptions) && !$draftContent) {
                                        unset($customOptionData['values'][$key]['option_type_id']);
                                    }
                                } elseif (isset($customOptionData['values'][$key]['option_type_id'])) {
                                    unset($customOptionData['values'][$key]['option_type_id']);
                                }
                            }
                        }
                    }
                    $customOption = $this->productCustomOption->create(['data' => $customOptionData]);
                    $customOption->setProductSku($product->getSku());
                    if ($previewFlag && !empty($customOptionData['values'])) {
                        $customOption->setValues($customOptionData['values']);
                    }

                    $customOptions[] = $customOption;
                    $i++;
                }
            }
            $product->setOptions($customOptions);
        }
    }

    /**
     * Merge product and default options for product.
     *
     * @param array $productOptions   product options
     * @param array $overwriteOptions default value options
     *
     * @return array
     */
    private function mergeProductOptions(array $productOptions, array $overwriteOptions): array
    {
        if (!is_array($productOptions)) {
            return [];
        }

        if (!is_array($overwriteOptions)) {
            return $productOptions;
        }

        foreach ($productOptions as $index => $option) {
            $optionId = $option['option_id'];

            if (!isset($overwriteOptions[$optionId])) {
                continue;
            }

            foreach ($overwriteOptions[$optionId] as $fieldName => $overwrite) {
                if ($overwrite && isset($option[$fieldName]) && isset($option['default_'.$fieldName])) {
                    $productOptions[$index][$fieldName] = $option['default_'.$fieldName];
                }
            }
        }

        return $productOptions;
    }

    protected function getOriginalProductOptions(ProductInterface $product)
    {
        if (empty($product->getOptions())) {
            return [];
        }
        $arrayProductOptions = [];
        foreach ($product->getOptions() as $option) {
            $optType = $option['type'] ?? $option->getType();
            $optionId = $option['option_id'] ?? $option->getOptionId();
            if ($optType == "drop-down" || $optType == "drop_down" || $optType == "radio") {
                $optionData = !empty($option['values']) ? $option['values'] : (is_object($option) ? $option->getValues() : []);
                $arrayProductOptions[$optionId] = is_array($optionData) ? array_keys($optionData) : [];
            } else {
                $arrayProductOptions[$optionId] = [];
            }
        }
        return $arrayProductOptions;

    }
}
