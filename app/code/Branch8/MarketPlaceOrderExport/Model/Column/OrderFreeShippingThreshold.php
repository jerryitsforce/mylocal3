<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class OrderFreeShippingThreshold implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('Order Free Shipping Threshold');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {

        if (isset($row['order_free_shipping_threshold'])) {
            return $row['order_free_shipping_threshold'];
        }
        return '';
    }
}
