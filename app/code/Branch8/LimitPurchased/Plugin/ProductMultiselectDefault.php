<?php

namespace Branch8\LimitPurchased\Plugin;

use Magento\Eav\Model\Config as EavConfig;

class ProductMultiselectDefault
{
    /**
     * @var EavConfig
     */
    protected $eavConfig;

    public function __construct(EavConfig $eavConfig)
    {
        $this->eavConfig = $eavConfig;
    }

    public function afterGetData(
        \Magento\Catalog\Ui\DataProvider\Product\Form\ProductDataProvider $subject,
        $result
    ) {
        if (!is_array($result) && !is_object($result)) {
            return $result;
        }
        
        $attributeCode = 'limit_purchased_customer_group';
        
        foreach ($result as $productId => $productData) {
            if(array_key_exists('product', $productData) && array_key_exists($attributeCode, $productData['product'])){
                continue;
            }
            $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);
            $options = $attribute->getSource()->getAllOptions(false);
            if (!empty($options)) {
                $values = array_column($options, 'value');
                $result[$productId]['product'][$attributeCode] = implode(',', $values);
            }
        }
        return $result;
    }
}