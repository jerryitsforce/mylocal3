<?php

namespace Branch8\Catalog\Plugin;

class ForceSaveProductStore0
{
    public function beforeUpdateAttributes($subject, $productIds, $attrData, $storeId)
    {
        $storeId = 0;
        return [$productIds, $attrData, $storeId];
    }
}