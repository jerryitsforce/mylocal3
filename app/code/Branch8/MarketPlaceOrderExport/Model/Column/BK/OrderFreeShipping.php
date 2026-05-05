<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class OrderFreeShipping implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('Order Fee Shipping');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if ($row['item_type'] === 'shipping') {
            return $row['invoice_order_item_price'];
        }
        if ($row['invoice_order_item_name'] === '訂單處理費') {
            return '';
        }
        return '';
    }
}
