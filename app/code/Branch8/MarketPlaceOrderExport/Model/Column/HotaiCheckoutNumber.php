<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class HotaiCheckoutNumber implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('Hotai Checkout Number');
    }


    public function processColumnData(array $row = [])
    {
        if (isset($row['ecpay_log_hotai_checkout_number'])) {
            return $row['ecpay_log_hotai_checkout_number'];
        }
        return $row['hotai_checkout_number'];
    }
}
