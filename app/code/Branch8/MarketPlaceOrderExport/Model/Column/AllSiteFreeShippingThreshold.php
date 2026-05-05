<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class AllSiteFreeShippingThreshold implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('All Site Free Shipping Threshold');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {

        if (isset($row['site_free_shipping_threshold'])) {
            return $row['site_free_shipping_threshold'];
        }
        return '';
    }
}
