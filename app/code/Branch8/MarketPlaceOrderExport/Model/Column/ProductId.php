<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class ProductId implements ColumnInterface
{
    public function getHeader()
    {
        return __('Product ID');
    }

    public function processColumnData(array $row = [])
    {
        if ($row['item_type'] !== 'item') {
            return '';
        }
        return $row['product_id'];
    }

}
