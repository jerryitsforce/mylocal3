<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Magento\Catalog\Model\Product\Type;

use Webkul\OptionsWithStockAndImages\Helper\Data as WebkulHelper;

class Price
{
    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    public $webkulHelper;

    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;
    
    /**
     * @var \Branch8\OptionsWithStockAndImages\Api\PriceManagementInterface
     */
    protected $priceManagement;

    public function __construct(
        WebkulHelper $webkulHelper,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Branch8\OptionsWithStockAndImages\Api\PriceManagementInterface $priceManagement
    ) {
        $this->webkulHelper = $webkulHelper;
        $this->_eventManager = $eventManager;
        $this->priceManagement = $priceManagement;
    }

    public function aroundGetFinalPrice(
        \Magento\Catalog\Model\Product\Type\Price $subject,
        \Closure $proceed,
        $qty,
        $product
    ) {
        $variationData = $this->getVariationPrice($product);
        if ($variationData !== null) {
            if ($qty === null && $product->getCalculatedFinalPrice() !== null) {
                return $product->getCalculatedFinalPrice();
            }
            
            $price = $variationData['price'];
            $isFinal = $variationData['is_final'];

            $product->setFinalPrice($price);

            if (!$isFinal) {
                // If price is raw (not from index), dispatch event to apply catalog rules
                $this->_eventManager->dispatch('catalog_product_get_final_price', ['product' => $product, 'qty' => $qty]);
                $price = $product->getData('final_price');
            }

            $finalPrice = max(0, $price);
            $product->setFinalPrice($finalPrice);

            return $finalPrice;
        }
        $result = $proceed($qty, $product);
        return $result;
    }

    protected function getVariationPrice($product)
    {
        $optionIds = $product->getCustomOption('option_ids');
        if($optionIds){
            $comb = "";
            $productRowId = $product->getRowId();
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
                foreach (explode(',', $optionIds->getValue() ?? '') as $optionId) {
                    if ($option = $product->getOptionById($optionId)) {
                        $confItemOption = $product->getCustomOption('option_' . $option->getId());
                        if($confItemOption){
                            $optDataArr = $optionData[$optionId];
                            if (isset($optDataArr) && isset($optDataArr[$confItemOption->getValue()])) {
                                $comb .= $optDataArr[$confItemOption->getValue()] . "_";
                            }
                        }
                    }
                }
                
                $comb = trim($comb, "_");
                
                // 1. Try to get calculated price from index
                $price = $this->priceManagement->getFinalPriceByComb((int)$product->getId(), $comb);
                if ($price !== null) {
                    return ['price' => $price, 'is_final' => true];
                }

                // 2. Fallback to raw variation price
                $variation = $this->webkulHelper->getCombData($productRowId, $comb);
                if ($variation->getId() && is_numeric($variation->getPrice())) {
                    return ['price' => $variation->getPrice(), 'is_final' => false];
                }
            }
        }
        return null;
    }
}
