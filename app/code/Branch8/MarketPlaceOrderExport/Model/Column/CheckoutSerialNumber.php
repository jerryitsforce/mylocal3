<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\DateFormat;

class CheckoutSerialNumber implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('Order Checkout Serial Number Modification Date');
    }

    /**
     *
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        // hard code like template
        $this->cached[$row['order_id']] = !empty($row['db_invoice_created_date']) ? $row['db_invoice_created_date'] : $row['order_date'];
        return $this->cached[$row['order_id']];
    }
}
