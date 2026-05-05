<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class CatalogPrice implements ColumnInterface
{

    public function getHeader()
    {
        return __('Catalog Price');
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
        if (empty($row["price_log"])) {
            return '';
        }
        $priceLog = $row["price_log"];
        if (empty($priceLog)) {
            return '';
        }
        $applied = json_decode($priceLog, true);
        if (empty($applied)) {
            return '';
        }

        return round($row['base_price_incl_tax']);
    }
}
