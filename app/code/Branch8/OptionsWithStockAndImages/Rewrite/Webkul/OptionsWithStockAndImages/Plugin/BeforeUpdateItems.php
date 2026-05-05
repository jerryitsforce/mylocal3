<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Rewrite\Webkul\OptionsWithStockAndImages\Plugin;

use Magento\Framework\Exception\StateException;

class BeforeUpdateItems extends \Webkul\OptionsWithStockAndImages\Plugin\BeforeUpdateItems
{
    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Logger\Logger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param \Webkul\OptionsWithStockAndImages\Helper\Data $helper
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Webkul\OptionsWithStockAndImages\Logger\Logger $logger
     */
    public function __construct(
        \Webkul\OptionsWithStockAndImages\Helper\Data $helper,
        \Magento\Checkout\Model\Cart $cart,
        \Webkul\OptionsWithStockAndImages\Logger\Logger $logger
    ) {
        $this->helper = $helper;
        $this->cart = $cart;
        $this->logger = $logger;
    }

    /**
     * Before Updating Item
     *
     * @param \Magento\Checkout\Model\Cart $subject
     * @param array $data
     * @return void
     */
    public function beforeUpdateItems(\Magento\Checkout\Model\Cart $subject, $data)
    {
        try {
            $productCombArr = [];
            $cart = $this->cart->getQuote();
            foreach ($cart->getAllItems() as $items) {
                if ((bool)$items->getData('available_to_checkout') === false) {
                    continue;
                }
                $comb = "";
                $product = $items->getProduct();
                $productRowId = $items->getProduct()->getRowId();
                if (!isset($productCombArr[$productRowId])) {
                    $productCombArr[$productRowId] = [];
                }
                foreach ($items->getOptions() as $option) {
                    if ($option->getCode()=="info_buyRequest") {
                        $decodeOptions = json_decode($option->getValue());
                        if (isset($decodeOptions->options)) {
                            $itemOptions = $decodeOptions;
                        }
                        break;
                    }
                }
                if (!empty($itemOptions)) {
                    $optionData = [];
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
                    $productCombArr = $this->getProductCombinations(
                        $itemOptions,
                        $optionData,
                        $productCombArr,
                        $productRowId,
                        $comb,
                        $items
                    );
                }
            }
            $notAvailableArr = [];
            $success = true;
            $quote = $subject->getQuote();
            foreach ($data as $key => $value) {
                $item = $quote->getItemById($key);
                if(!$item->getAvailableToCheckout()){
                    continue;
                }
                if($item && $item->getId() && $item->getProductId()){
                    $comb = "";
                    $product = $item->getProduct();
                    $productRowId = $product->getRowId();
                    if (!isset($productCombArr[$productRowId])) {
                        $productCombArr[$productRowId] = [];
                    }
                    $itemQty= $value['qty'];
                    foreach ($item->getOptions() as $option) {
                        if ($option->getCode()=="info_buyRequest") {
                            $decodeOptions = json_decode($option->getValue());
                            if (isset($decodeOptions->options)) {
                                $itemOptions = $decodeOptions;
                            }
                            break;
                        }
                    }
                    if (!empty($itemOptions)) {
                        $optionData = [];
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
                        if (!empty($optionData)) {
                            $variationsArr = $this->getProductVariationsData(
                                $itemOptions,
                                $optionData,
                                $productRowId,
                                $comb,
                                $productCombArr,
                                $success,
                                $notAvailableArr,
                                $itemQty,
                                $item
                            );
                            $productCombArr = $variationsArr['productCombArr'];
                            $notAvailableArr = $variationsArr['notAvailableArr'];
                            $success = $variationsArr['success'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
        $notAvailableArr = array_unique($notAvailableArr);
        $notAvailable = implode(", ", $notAvailableArr);
        if (!$success) {
            throw new StateException(__(
                'We do not have %1 as many as you are trying to order.',
                $notAvailable
            ));
        }
    }

     /**
      * Get Product Combinations Data
      *
      * @param string $itemOptions
      * @param array $optionData
      * @param array $productCombArr
      * @param int $productId
      * @param string $comb
      * @param \Magento\Checkout\Model\Cart $items
      * @return array
      */
    public function getProductCombinations(
        $itemOptions,
        $optionData,
        $productCombArr,
        $productId,
        $comb,
        $items
    ) {
        $options = $itemOptions;
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

     /**
      * Get Product Variations Data
      *
      * @param string $itemOptions
      * @param array $optionData
      * @param int $productId
      * @param string $comb
      * @param array $productCombArr
      * @param boolean $success
      * @param array $notAvailableArr
      * @param int $itemQty
      * @param \Magento\Checkout\Model\Cart $item
      * @return array
      */
    public function getProductVariationsData(
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
        $options = $itemOptions;
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
