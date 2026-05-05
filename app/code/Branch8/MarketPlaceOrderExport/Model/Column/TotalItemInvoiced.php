<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class TotalItemInvoiced implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('Invoice Tax Amount');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if ($row['item_type'] === 'shipping') {
            return (int)$row['shipping_price_incl_tax'];
        }

        if ($row['invoice_order_item_name'] === '訂單處理費') {
            return (int)$row['invoice_order_item_price'];
        }
        //getting from product ??
        return (int)$row['row_total_incl_tax'] - (int)$row['row_total_point_used'] - (int)$row['discount_amount'];
    }
}
