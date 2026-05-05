<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class VendorSku implements ColumnInterface
{
    public function getHeader()
    {
        return __('Vendor Sku');
    }

    public function processColumnData(array $row = [])
    {   ///
        if ($row['item_type'] !== 'item') {
            return '';
        }
        $replacePrefix = false;
        if (!empty($row['variation_sku'])) {
            $vendorSku = $row['variation_sku'];
        } elseif (!empty($row['option_sku'])) {
            $vendorSku = $row['option_sku'];
        } elseif (!empty($row['origin_sku'])) {
            $vendorSku = $row['origin_sku'];
            $replacePrefix = true;
        } else {
            $replacePrefix = true;
            $vendorSku =  $row['product_sku'];
        }
        return $replacePrefix ? preg_replace("/^HOTAI[^-]*-/", "", (string)$vendorSku) : $vendorSku;
    }
}
