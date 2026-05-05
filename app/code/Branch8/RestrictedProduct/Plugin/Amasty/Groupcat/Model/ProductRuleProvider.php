<?php

namespace Branch8\RestrictedProduct\Plugin\Amasty\Groupcat\Model;

class ProductRuleProvider
{
    public function aroundgetRuleForProduct($subject, $proceed, $product)
    {
        if($product->getId() === null){
            return [];
        }
        return $proceed($product);
    }
}