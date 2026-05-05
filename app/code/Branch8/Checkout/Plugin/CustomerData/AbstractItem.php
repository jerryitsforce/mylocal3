<?php

namespace Branch8\Checkout\Plugin\CustomerData;

class AbstractItem
{

    public function afterGetItemData($subject, $result, $item)
    {
        $product = $item->getProduct();
        $individualProduct = $product->getData('individual_product');
        $result['individual_product'] = $individualProduct;

        return $result;
    }

}