<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Product;

use Magento\Catalog\Api\Data\ProductExtension;
use DOMDocument;


class GetChangedData
{
    /**
     * @var array
     */
    private array $ignoredAttributes = [
        'website_ids'
    ];

    protected $entityAttribute;

    public function __construct(
        \Magento\Eav\Model\Entity\Attribute $entityAttribute
    ) {
        $this->entityAttribute = $entityAttribute;
    }

    /**
     * Retrieve changed data of a product.
     *
     * @param array $currentData
     * @param array $originalData
     *
     * @return array
     */
    public function execute(array $currentData, array $originalData, $isNew = false): array
    {
        $changedData = [];

        foreach ($currentData as $key => $value) {
            if (in_array($key, $this->ignoredAttributes)) {
                continue;
            }
            if($key == 'editor_updated_fields'){
                /** This field to check which seller editor fields are updated */
                continue;
            }
            if (!is_array($value)) {
                if ($isNew) {
                    $originalData[$key] = null;
                }
            }
            if (is_array($value)) {
                if ($key === 'stock_data') { // Check changes related to stock data
                    foreach ($value as $k => $val) {
                        /** @var ProductExtension $extensionAttributes */
                        if ($k == 'manage_stock') continue;
                        if ($isNew) {
                            $changedData[$key]['before'][$k] = null;
                            $changedData[$key]['after'][$k] = $val;
                            continue;
                        }
                        $extensionAttributes = $originalData['extension_attributes'] ?? null;
                        $stockItem = $extensionAttributes?->getStockItem();
                        $val = is_string($val) ? trim($val) : $val;
                        if ($stockItem?->getData($k) != $val && (int)$stockItem?->getData($k) != (int)$val) {
                            $changedData[$key]['before'][$k] = $stockItem->getData($k);
                            $changedData[$key]['after'][$k] = $val;
                        }
                    }
                }
                elseif ($key == 'quantity_and_stock_status') {
                    // Change code for hotfix HTGO2-2621
                    // Data originalData { ["is_in_stock"]=> bool(true) ["qty"]=> float(10) }
                    // Data value { ["qty"]=> string(3) "100" ["is_in_stock"]=> int(1) }
                    // if check is_in_stock from arraykeys and array_diff will not work
                    // Comment unset is_in_stock because this value will auto change by qty
                    //unset($originalData['quantity_and_stock_status']['is_in_stock']);
                    $diff1 = array_diff_assoc($value, $originalData[$key]);
                    $diff2 = array_diff_assoc($originalData[$key], $value);
                    if (!empty($diff1) || !empty($diff2)) {
                        $changedData[$key] = ['before' => $originalData[$key] ?: null, 'after' => $value];
                    }
                }

                elseif ($key =='shipping_method') {
                    $originShippingMethod = (isset($originalData['shipping_method']) && $originalData['shipping_method']) ? explode(',' , $originalData['shipping_method']) : [];
                    if ($isNew) {
                        $originShippingMethod = [];
                    }
                    if (!$currentData[$key]) {
                        continue;
                    }
                    $diff1 = array_diff_assoc($currentData[$key], $originShippingMethod);
                    $diff2 = array_diff_assoc($originShippingMethod, $currentData[$key]);

                    if (!empty($diff1) || !empty($diff2)) {
                        $changedData[$key] = ['before' => (!empty($originShippingMethod)) ? $originShippingMethod : null, 'after' => $value];
                    }

                } else {
                    $originalValue = $originalData[$key] ?? [];
                    if ($isNew) {
                        $originalValue = [];
                    }
                    $re = '/^\d+(?:,\d+)*$/';
                    if (is_string($originalValue) && preg_match($re, $originalValue) ) {
                        $originalValue = explode(',',$originalValue);
                    }
                    $originalValue = is_array($originalValue) ? $originalValue : [$originalValue];
                    if ($key === 'image_gallery') { // Check changes related to image gallery
                        foreach ($originalValue as $k => $val) {
                            if (!array_key_exists($k, $value)) {
                                $val['removed'] = 1;
                                $value[$k] = $val;
                            }
                        }
                        $diff1 = $this->recursiveArrayDiff($originalValue, $value);
                        $diff2 = $this->recursiveArrayDiff($value, $originalValue);
                    } elseif (in_array($key, ['downloadable_link', 'downloadable_sample'])) { // Check changes related to downloadable product
                        $ignoredKeys = ['file'];
                        $diff1 = $this->recursiveArrayDiff($originalValue, $value, $ignoredKeys);
                        $diff2 = $this->recursiveArrayDiff($value, $originalValue, $ignoredKeys);
                    } elseif ($key === 'links') { // Check changes related to group product
                        $ignoredKeys = ['file'];
                        $diff1 = $this->recursiveArrayDiff($originalValue, $value, $ignoredKeys);
                        $diff2 = $this->recursiveArrayDiff($value, $originalValue, $ignoredKeys);
                    } elseif (in_array($key, BuildConfigurableProduct::getRequiredKeys())) { // Check changes related to configurable product
                        if ($key === BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS) {
                            $diff1 = array_diff($originalValue, $value);
                            $diff2 = array_diff($value, $originalValue);
                        } else {
                            $diff1 = $this->recursiveArrayDiff($originalValue, $value);
                            $diff2 = $this->recursiveArrayDiff($value, $originalValue);
                        }
                    } elseif (in_array($key, BuildProductLinks::getProductLinkTypes())) { // Check changes related to configurable product
                        $change = false;
                        foreach ($value as $link) {
                            if (!in_array($link, $originalValue)) {
                                $change = true;
                                break;
                            }
                        }
                        if ($change) {
                            $diff1 = array_diff_assoc($originalValue, $value);
                            $diff2 = array_diff_assoc($value, $originalValue);
                        }
                    } elseif (in_array($key, BuildBundleProduct::getRequiredKeys())) { // Check changes related to configurable product
                        if ($key == BuildBundleProduct::BUNDLE_OPTIONS) {
                            foreach ($value as $k => $valueDetail) {
                                if (isset($valueDetail['option_id'])) {
                                    $value[$valueDetail['option_id']] = $valueDetail;
                                    unset($value[$k]);
                                }
                            }
                        }
                        if ($key == BuildBundleProduct::BUNDLE_SELECTIONS) {
                            foreach ($value as $k => $valueDetail) {
                                foreach ($valueDetail as $valueDetailOption) {
                                    if (isset($valueDetailOption['option_id']) && isset($valueDetailOption['selection_id'])) {
                                        $value[$valueDetailOption['option_id']][$valueDetailOption['selection_id']] = $valueDetailOption;
                                        unset($value[$k]);
                                    }
                                }
                            }
                        }
                        if ($key !== BuildBundleProduct::AFFECT_BUNDLE_SELECTIONS) {
                            $diff1 = $this->recursiveArrayDiff($originalValue, $value, ['name','sku','price']);
                            $diff2 = $this->recursiveArrayDiff($value, $originalValue, ['name','sku','price']);
                        }
                    } elseif ($key === 'tier_price') {
                        $diff1 = $this->recursiveArrayDiff($originalValue, $value, ['all_groups', 'percentage_value', 'value_type', 'product_id', 'website_price'], ['delete']);
                        $diff2 = $this->recursiveArrayDiff($value, $originalValue, ['all_groups', 'percentage_value', 'value_type', 'product_id', 'website_price'], ['delete']);
                    } elseif ($key === BuildCustomOptions::KEY_CUSTOM_OPTIONS) { // Check changes related to custom options
                        $originalCompareValue = [];
                        foreach ($originalValue as $valSort) {
                            $originalCompareValue[$valSort->getOptionId()] = $valSort->getData();
                            if ($valSort->getSku()) {
                                unset($originalCompareValue[$valSort->getOptionId()]['sku']);
                            }
                            if (in_array($valSort->getType(), ['drop_down', 'radio', 'checkbox', 'multiple'])) {
                                foreach ($valSort->getValues() as $optionValue) {
                                    $originalCompareValue[$valSort->getOptionId()]['values'][$optionValue->getOptionTypeId()] = $optionValue->getData();
                                }
                            }
                        }
                        foreach ($value as $k => $valueDetail) {
                            if (isset($valueDetail['option_id']) && $valueDetail['option_id'] > 0) {
                                if (!empty($valueDetail['values'])) {
                                    if ($k == $valueDetail['option_id']) {
                                        foreach ($valueDetail['values'] as $k1 => $valueDetailOption) {
                                            $value[$k]['values'][$k1]['option_id'] = $valueDetail['option_id'];
                                        }
                                    } else {
                                        foreach ($valueDetail['values'] as $k1 => $valueDetailOption) {
                                            if (isset($valueDetailOption['option_type_id']) && $valueDetailOption['option_type_id'] > 0) {
                                                $value[$k]['values'][$valueDetailOption['option_type_id']] = $valueDetailOption;
                                                $value[$k]['values'][$valueDetailOption['option_type_id']]['option_id'] = $valueDetail['option_id'];
                                                if ($k1 != $valueDetailOption['option_type_id']) unset($value[$k]['values'][$k1]);
                                            } elseif (isset($valueDetailOption['value_id']) && $valueDetailOption['value_id'] > 0) {
                                                $value[$k]['values'][$valueDetailOption['value_id']] = $valueDetailOption;
                                                $value[$k]['values'][$valueDetailOption['value_id']]['option_id'] = $valueDetail['option_id'];
                                                $value[$k]['values'][$valueDetailOption['value_id']]['option_type_id'] = $valueDetailOption['value_id'];
                                                unset($value[$k]['values'][$valueDetailOption['value_id']]['value_id']);
                                                if ($k1 != $valueDetailOption['value_id']) unset($value[$k]['values'][$k1]);
                                            } else {
                                                $value[$k]['values'][$k1]['option_id'] = $valueDetail['option_id'];
                                            }
                                        }
                                        $value[$valueDetail['option_id']] = $value[$k];
                                        unset($value[$k]);
                                    }
                                }
                            }
                        }
                        $originalValue = $this->filterComparedData($originalCompareValue, $value, 'sku');
                        $ignoredKeys = ['extension_attributes','record_id','product_id','previous_group','previous_type','default_price','default_price_type','store_price','store_price_type','default_title','store_title','product_item_id','is_bought'];
                        $skipKeysNull = ['is_delete','max_characters','price','price_type','sku','image_size_x','image_size_y','file_extension'];

                        $diff1 = $this->recursiveArrayDiff($originalValue, $value, $ignoredKeys, $skipKeysNull);
                        $diff2 = $this->recursiveArrayDiff($value, $originalValue, $ignoredKeys, $skipKeysNull);
                        if (empty($diff1) && empty($diff2) && isset($currentData['co_variation'])) {
                            unset($currentData['co_variation']);
                            if (isset($changedData['co_variation'])) {
                                unset($changedData['co_variation']);
                            }
                        }
                    } elseif ($key == 'limit_purchased_customer_group') {
                        if (empty($originalData[$key]) && empty(array_filter($value))) {
                            continue;
                        }
                        $v2 = $originalData[$key] ?? [];
                        if (!empty($originalData[$key]) && is_string($originalData[$key])) {
                            $v2 = explode(',', $originalData[$key]);
                        }
                        if ($isNew) {
                            $v2 = [];
                        }
                        $diff1 = array_diff_assoc($v2, $value);
                        $diff2 = array_diff_assoc($value, $v2);
                    } elseif ($key == 'category_ids') {
                        if (is_array($originalValue)) {
                            sort($originalValue);
                            $arrayCategory = $originalValue;
                            if (!empty($originalData['main_category'])) {
                                $arrayCategory = array_diff($arrayCategory, [$originalData['main_category']]);
                            }
                            if (!empty($originalData['flagstore_category'])) {
                                $arrayCategory = array_diff($arrayCategory, [$originalData['flagstore_category']]);
                            }
                            $value = array_merge($value, $arrayCategory);
                            sort($value);
                        }
                        $diff1 = array_diff_assoc($originalValue, $value);
                        $diff2 = array_diff_assoc($value, $originalValue);
                    } else {
                        $diff1 = array_diff_assoc($originalValue, $value);
                        $diff2 = array_diff_assoc($value, $originalValue);
                    }
                    if (!empty($diff1) || !empty($diff2)) {
                        $changedData[$key] = ['before' => $originalValue ?: null, 'after' => $value];
                    }
                }
            } else {
                /*if (($key == 'recommendation' || $key == 'short_description')
                    && isset($originalData[$key]) && $originalData[$key] && $value) {
                    $v1 = str_replace(array("\r", "\n"), '', $originalData[$key]);
                    $v1 = $v1 ? trim($v1) : '';
                    $v1 = $v1 ? strip_tags($v1) : '';
                    $v2 = str_replace(array("\r", "\n"), '', $value);
                    $v2 = $v2 ? trim($v2) : '';
                    $v2 = $v2 ? strip_tags($v2) : '';
                    if (!strcmp($v1,$v2)) continue;
                }*/
                if (isset($originalData[$key]) && $originalData[$key] == 'no_selection') {
                    $originalData[$key] = null;
                }
                if ($value == 'no_selection') {
                    $value = null;
                }

                if ((isset($originalData[$key]) && trim((string)$originalData[$key]) != trim((string)$value))
                    || (!isset($originalData[$key]) && !empty(trim((string)$value)))
                ) {
                    if (($key === 'special_from_date' || $key === 'special_to_date') && isset($originalData[$key]) && $value) {
                        if (strtotime($originalData[$key]) != strtotime($value))
                            $changedData[$key] = ['before' => $originalData[$key] ?? null, 'after' => $value];
                    } elseif (($key == 'recommendation' || $key == 'specification' || $key == 'note' || $key == 'description')
                        && isset($originalData[$key]) && !empty(trim((string)$value))) {
                        if (isset($currentData['editor_updated_fields'])) {
                            $editorUpdatedFields = $currentData['editor_updated_fields'];
                            $editorUpdatedFieldsArr = explode(',', $editorUpdatedFields);
                            if (!in_array($key, $editorUpdatedFieldsArr)) {
                                continue;
                            }
                        }
                        $dom1 = new DOMDocument();
                        $dom2 = new DOMDocument();

                        // Suppress errors due to HTML5 tags not supported by DOMDocument
                        libxml_use_internal_errors(true);

                        $dom1->loadHTML($currentData[$key]);
                        $dom2->loadHTML($originalData[$key]);

                        libxml_clear_errors();
                        $isChanged = $dom1->saveHTML($dom1->documentElement) !== $dom2->saveHTML($dom2->documentElement);
                        if ($isChanged) {
                            $text1 = $currentData[$key];
                            $text2 = preg_replace('/\s+/', '', $originalData[$key]);
                            if (strcmp($text1, $text2) !== 0) {
                                $changedData[$key] = ['before' => $originalData[$key] ?? null, 'after' => $value];
                            }
                        }
                    } else if($key == 'wk_marketplace_preorder'){
                        /**
                         * Can not update exited data, too many
                         * NULL = default = disable
                         */
                        $preOrderDisable = '';
                        $preorderAttr = $this->entityAttribute->loadByCode('catalog_product', 'wk_marketplace_preorder');
                        $preorderMappping = [];
                        foreach ($preorderAttr->getSource()->getAllOptions() as $option){
                            $preorderMappping[$option['value']] = $option['label'];
                            if($option['label'] == __('Disable')){
                                $preOrderDisable = $option['value'];
                            }
                        }
                        $preorderBefore = null;
                        if(isset($originalData[$key])){
                            $preorderBefore = $originalData[$key];
                        }
                        if($preorderBefore == NULL){
                            /** Default = Disable, so many place set null, many place set Disable */
                            $preorderBefore = $preOrderDisable;
                        }
                        if($preorderBefore != $value){
                            $changedData[$key] = ['before' => $preorderBefore , 'after' => $value];
                        }
                    } else {
                        $changedData[$key] = ['before' => $originalData[$key] ?? null, 'after' => $value];
                    }
                }
            }
        }

        return $changedData;
    }

    /**
     * Recursively compare two multidimensional arrays and return the differences.
     *
     * @param array $array1
     * @param array $array2
     * @param array $ignoredKeys
     * @param array $skipKeysNull
     *
     * @return array
     */
    public function recursiveArrayDiff(array $array1, array $array2, array $ignoredKeys = [], array $skipKeysNull = []): array
    {
        $diff = [];
        $array1 = array_filter($array1);
        foreach ($array1 as $key => $value) {
            if (in_array($key, $ignoredKeys)) {
                continue;
            }
            if (in_array($key, $skipKeysNull)) {
                if ($key == 'price') $value = (int) $value;
                if (!is_array($value) && (!$value || $value == 'N/A')) continue;
            }
            if (is_array($value)) {
                if (isset($array2[$key]) && is_array($array2[$key])) {
                    $recursiveDiff = $this->recursiveArrayDiff($value, $array2[$key], $ignoredKeys, $skipKeysNull);
                    if (!empty($recursiveDiff)) {
                        $diff[$key] = $recursiveDiff;
                    }
                } else {
                    $diff[$key] = $value;
                }
            } else {
                $value1 = ($value === '') ? null : $value;
                $value2 = isset($array2[$key]) ? ($array2[$key] === '' ? null : $array2[$key]) : null;

                if (!array_key_exists($key, $array2) || $value1 != $value2) {
                    $diff[$key] = $value;
                }
            }
        }
        return $diff;
    }

    public function filterComparedData($a, $b, $matchKeyForValues = 'sku') {
        $result = [];
        foreach ($b as $key => $bVal) {
            if (!isset($a[$key])) {
                continue;
            }
            $aVal = $a[$key];
            if ($key === 'values' && is_array($bVal) && is_array($aVal)) {
                // Build a lookup from A's values by sku
                $aSubsBySku = [];
                foreach ($aVal as $aSub) {
                    if (isset($aSub[$matchKeyForValues])) {
                        $aSubsBySku[$aSub[$matchKeyForValues]] = $aSub;
                    }
                }
                $filteredSubs = [];
                foreach ($bVal as $bSubKey => $bSubVal) {
                    if (isset($bSubVal[$matchKeyForValues]) &&
                        isset($aSubsBySku[$bSubVal[$matchKeyForValues]]))
                    {
                        // Recursively filter the sub-object to B's structure
                        $filteredSubs[$bSubKey] = $this->filterComparedData(
                            $aSubsBySku[$bSubVal[$matchKeyForValues]],
                            $bSubVal,
                            $matchKeyForValues
                        );
                    }
                }
                $result[$key] = $filteredSubs;
            } elseif (is_array($bVal) && is_array($aVal)) {
                $result[$key] = $this->filterComparedData($aVal, $bVal, $matchKeyForValues);
            } else {
                $result[$key] = $aVal;
            }
        }
        return $result;
    }
}
