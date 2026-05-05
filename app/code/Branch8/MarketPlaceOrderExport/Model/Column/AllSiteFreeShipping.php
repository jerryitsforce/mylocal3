<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class AllSiteFreeShipping implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('All Site Fee Shipping');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if (isset($row['site_shipping_fee'])) {
            return $row['site_shipping_fee'];
        }
        return '';
    }
}
