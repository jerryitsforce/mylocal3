<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class SpecialPrice implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('Special Price');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if ($row['item_type'] === 'shipping') {
            return '';
        }

        if ($row['invoice_order_item_name'] === '訂單處理費') {
            return '';
        }

        return $row['special_price'];
    }
}
