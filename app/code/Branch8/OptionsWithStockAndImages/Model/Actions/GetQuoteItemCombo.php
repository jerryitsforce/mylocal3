<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       10/02/2026
 */

namespace Branch8\OptionsWithStockAndImages\Model\Actions;

class GetQuoteItemCombo
{
    private $cache = [];

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return mixed|string
     */
    public function get(\Magento\Quote\Model\Quote\Item $item)
    {
        if (isset($this->cache[$item->getId()]) && $item->getId()) {
            return $this->cache[$item->getId()];
        }
        $product = $item->getProduct();
        $combo = '';
        if ($product && ($productOptions = $this->getProductOptionData($product))) {
            //$productOptions = $this->getProductOptionData($product);
            $combo = $this->getItemCombo($item, $productOptions);
            if ($item->getId()) {
                $this->cache[$item->getId()] = $combo;
            }
        }
        return $combo;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param $optionData
     * @return string
     */
    private function getItemCombo(\Magento\Quote\Model\Quote\Item $item, $optionData = [])
    {
        $comb = '';
        $itemOptions = $item->getOptionByCode('info_buyRequest') ? json_decode(
            $item->getOptionByCode('info_buyRequest')->getValue()
        ) : [];
        if (empty($itemOptions) || empty($itemOptions->options)) {
            return '';
        }
        foreach ($itemOptions->options as $key => $value) {
            if (isset($optionData[$key]) && isset($optionData[$key][$value])) {
                $comb .= $optionData[$key][$value] . "_";
            }
        }
        return trim($comb, "_");
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    private function getProductOptionData(\Magento\Catalog\Model\Product $product)
    {
        $optionData = [];
        if (empty($product->getOptions())) {
            return $optionData;
        }
        foreach ($product->getOptions() as $option) {
            $optType = $option->getType();
            if ($optType == "drop-down" || $optType == "drop_down" || $optType == "radio") {
                $optionId = $option->getId();
                $optionData[$optionId] = [];
                foreach ($option->getValues() as $value) {
                    $valueId = $value->getId();
                    $optionData[$optionId][$valueId] = $value->getDefaultTitle();
                }
            }
        }
        return $optionData;
    }
}
