<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Webkul\OptionsWithStockAndImages\Observer;

class CustomWeight
{
    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    public $cart;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Logger\Logger
     */
    public $logger;

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

    public function aroundExecute(
        \Webkul\OptionsWithStockAndImages\Observer\CustomWeight $subject,
        \Closure $proceed,
        \Magento\Framework\Event\Observer $observer
    ) {
        // Disable the custom weight functionality
        return;
        
        try {
            $combArr = [];
            $item = $observer->getEvent()->getData('quote_item');
            $item = ($item->getParentItem() ? $item->getParentItem() : $item);
            if ((bool)$item->getData('available_to_checkout') === false) {
                return;
            }
            $product = $item->getProduct();
            $productId = $item->getProductId();
            $productRowId = $product->getRowId();
            //$optionData = $this->helper->getOptionData($productId);
            $optionData = [];
            if(!is_null($product->getOptions())) {
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
            }
            if (!empty($optionData)) {
                $cart = $this->cart->getQuote();
                $combArr = $this->getTotalQuantity($cart, $productId, $optionData, $combArr);
                $itemOptions = [];
                foreach ($item->getOptions() as $option) {
                    if ($option->getCode() == "info_buyRequest") {
                        $decodeOptions = json_decode($option->getValue());
                        if (isset($decodeOptions->options)) {
                            $itemOptions = $decodeOptions;
                        }
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
        if (!empty($itemOptions)) {
            $comb = "";
            $options = $itemOptions;
            if (isset($options->options)) {
                foreach ($options->options as $key => $value) {
                    if (isset($optionData[$key]) && isset($optionData[$key][$value])) {
                        $comb .= $optionData[$key][$value]."_";
                    }
                }
            }
            $comb = trim($comb, "_");
            $variation = $this->helper->getCombData($productRowId, $comb);
            if ($variation->getId()) {
                if (($variation->getStock()-$combArr[$comb])<0) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __("Requested quantity not available for requested combination.")
                    );
                } else {
                    $item->getProduct()->setIsSuperMode(true);
                    if ($variation->getWeight()!=null) {
                        $item->setWeight($variation->getWeight());
                    }
                }
            }
        }
    }

    /**
     * Get Total Quantity for each combination
     *
     * @param \Magento\Checkout\Model\Cart $cart
     * @param int $productId
     * @param array $optionData
     * @param array $combArr
     * @return array
     */
    private function getTotalQuantity($cart, $productId, $optionData, $combArr)
    {
        foreach ($cart->getAllItems() as $items) {
            if ($items->getProductId()==$productId) {
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
                    $combArr = $this->getCombinationsData(
                        $itemOptions,
                        $items,
                        $optionData,
                        $combArr
                    );
                }
            }
        }
        return $combArr;
    }

    /**
     * GetCombinationsData
     *
     * @param string $itemOptions
     * @param \Magento\Checkout\Model\Cart $items
     * @param array $optionData
     * @param array $combArr
     * @return array
     */
    private function getCombinationsData($itemOptions, $items, $optionData, $combArr)
    {
        try {
            $options = $itemOptions;
            if (isset($options->options)) {
                $comb = "";
                foreach ($options->options as $key => $value) {
                    if (isset($optionData[$key]) && isset($optionData[$key][$value])) {
                        $comb .= $optionData[$key][$value]."_";
                    }
                }
                $comb = trim($comb, "_");
                if ($comb) {
                    if (isset($combArr[$comb])) {
                        $combArr[$comb] += $items->getQty();
                    } else {
                        $combArr[$comb] = $items->getQty();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
        return $combArr;
    }
}
