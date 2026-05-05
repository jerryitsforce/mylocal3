<?php
namespace Branch8\Wishlist\Observer;

class CustomSavePrice implements \Magento\Framework\Event\ObserverInterface
{
    protected $wkHelper;

    public function __construct(
        \Webkul\OptionsWithStockAndImages\Helper\Data $wkHelper
    )
    {
        $this->wkHelper = $wkHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try{
            $items = $observer->getData('items');
            foreach($items as &$_item){
                $product = $_item->getProduct();
                $productFePrice = $product->getFinalPrice();
                
                // $requestInfor = $_item->getBuyRequest();
                // $options = $requestInfor->getOptions();
                // if(is_array($options)){
                //     $newOptions = [];
                //     foreach($options as $oId => $oVal){
                //         $newOptions[] = [
                //             'option_id' => $oId,
                //             'option_value' => $oVal
                //         ];
                //     }
                //     $productFePrice = $this->getCustomOptionsPrice($product, $newOptions);
                // }
                $_item->setPrice($productFePrice)->save();
                
            }
        }catch(\Exception $e){

        }
    }

    public function getCustomOptionsPrice(\Magento\Catalog\Model\Product $product, array $options)
    {
        $rowId = $product->getRowId();
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
        $comb = '';
        foreach ($options as $option) {
            $optDataArr = $optionData[$option['option_id']];
            if (isset($optDataArr) && isset($optDataArr[$option['option_value']])) {
                $comb .= $optDataArr[$option['option_value']] . "_";
            }
        }

        $comb = trim($comb, "_");

        $variantData = $this->wkHelper->getCombData($rowId, $comb);
        return ($variantData->getId()) ? $variantData->getPrice() : 0;
    }

}