<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class CartPriceDiscount implements ColumnInterface
{

    public function getHeader()
    {
        return __('Cart Price Discount');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {

        return round((int)$row['base_discount_amount']);
    }
}
