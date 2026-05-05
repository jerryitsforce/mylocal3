<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Webkul\OptionsWithStockAndImages\Plugin;

class BeforeUpdateItems
{
    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    protected $helper;

    /**
     * Constructor
     *
     * @param \Webkul\OptionsWithStockAndImages\Helper\Data $helper
     */
    public function __construct(
        \Webkul\OptionsWithStockAndImages\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    public function aroundGetProductCombinations(
        \Webkul\OptionsWithStockAndImages\Plugin\BeforeUpdateItems $subject,
        \Closure $proceed,
        $itemOptions,
        $optionData,
        $productCombArr,
        $productId,
        $comb,
        $items
    ) {
        $options = json_decode($itemOptions);
        if (isset($options->options)) {
            foreach ($options->options as $key => $value) {
                if (isset($optionData[$key]) && isset($optionData[$key][$value])) {
                    $comb .= $optionData[$key][$value]."_";
                }
            }
            $comb = trim($comb, "_");
            if ($comb) {
                if (isset($productCombArr[$productId][$comb])) {
                    $productCombArr[$productId][$comb] += $items->getQty();
                } else {
                    $productCombArr[$productId][$comb] = $items->getQty();
                }
            }
        }
        return $productCombArr;
    }

    public function aroundGetProductVariationsData(
        \Webkul\OptionsWithStockAndImages\Plugin\BeforeUpdateItems $subject,
        \Closure $proceed,
        $itemOptions,
        $optionData,
        $productId,
        $comb,
        $productCombArr,
        $success,
        $notAvailableArr,
        $itemQty,
        $item
    ) {
        $options = json_decode($itemOptions);
        if (isset($options->options)) {
            foreach ($options->options as $key => $value) {
                if (isset($optionData[$key]) && isset($optionData[$key][$value])) {
                    $comb .= $optionData[$key][$value]."_";
                }
            }
        }

        $comb = trim($comb, "_");
        $variation = $this->helper->getCombData($productId, $comb);
        if ($variation->getId()) {
            if (isset($productCombArr[$productId][$comb])) {
                $productCombArr[$productId][$comb] += $itemQty;
                $productCombArr[$productId][$comb] -= $item->getQty();
            } else {
                $productCombArr[$productId][$comb] = $itemQty;
            }
            if (($variation->getStock()-$productCombArr[$productId][$comb])<0) {
                $success = false;
                $notAvailableArr[] = $item->getName()." (".$comb.")";
                $productCombArr[$productId][$comb] -= $itemQty;
                $productCombArr[$productId][$comb] += $item->getQty();
            }
        }
        
        return [
            'success' => $success,
            'notAvailableArr' => $notAvailableArr,
            'productCombArr' => $productCombArr
        ];
    }
}