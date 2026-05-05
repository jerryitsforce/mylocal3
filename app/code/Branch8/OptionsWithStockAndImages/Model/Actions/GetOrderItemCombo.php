<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       10/02/2026
 */

namespace Branch8\OptionsWithStockAndImages\Model\Actions;

class GetOrderItemCombo
{
    private $cache = [];

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return mixed|string
     */
    public function get(\Magento\Sales\Model\Order\Item $item)
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
     * @param \Magento\Sales\Model\Order\Item $item
     * @param $optionData
     * @return string
     */
    private function getItemCombo(\Magento\Sales\Model\Order\Item $item, $optionData = [])
    {
        $comb = '';
        $options = $item->getProductOptions();
        if (!empty($options['options'])) {
            foreach ($options['options'] as $option) {
                $optDataArr = $optionData[$option['option_id']];
                if (isset($optDataArr[$option['option_value']])) {
                    $comb .= $optDataArr[$option['option_value']] . "_";
                }
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
