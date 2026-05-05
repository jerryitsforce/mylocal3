<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class ProductSku implements ColumnInterface
{
    public function getHeader()
    {
        return __('Product Sku');
    }

    public function processColumnData(array $row = [])
    {
        if ($row['item_type'] !== 'item') {
            return '';
        }
        return $row['product_sku'];
    }

}
