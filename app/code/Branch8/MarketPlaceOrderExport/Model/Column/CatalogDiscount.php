<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class CatalogDiscount implements ColumnInterface
{
    public function getHeader()
    {
        return __('Catalog Discount');
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
        $discount = 0;
        if (empty($row["price_log"])) {
            return $discount;
        }
        $priceLog = $row["price_log"];
        if (empty($priceLog)) {
            return $discount;
        }
        $applied = json_decode($priceLog, true);
        if (empty($applied)) {
            return $discount;
        }
        /**
         * @see Branch8/HifiSalesReport/Helper/Report.php (551-553)
         */
        $priceInclTax = $row['sales_order_item_price_incl_tax'];
        $specialPrice = $row['special_price'];
        $originalPrice = $row['original_price'];
        $discount = ($specialPrice ?? $originalPrice) - $priceInclTax;
        return round($discount) * $row['qty'];
    }
}
