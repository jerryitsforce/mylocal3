<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class Cost implements ColumnInterface
{
    public function getHeader()
    {
        return __('Cost');
    }

    public function processColumnData(array $row = [])
    {
        if (empty($row['cost']) || is_null($row['cost']) ||  $row['item_type'] === 'shipping' || $row['invoice_order_item_name'] === '訂單處理費') {
            return '';
        }
        return round($row['cost']);
    }
}
