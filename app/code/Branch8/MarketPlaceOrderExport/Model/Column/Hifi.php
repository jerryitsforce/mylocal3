<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class Hifi implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('HIFI (Fixd)');
    }

    /**
     *
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if (isset($this->cached[$row['order_id']])) {
            return $this->cached[$row['order_id']];
        }
        // hard code like template
        $this->cached[$row['order_id']] = '銷貨收入.點數商城';
        return $this->cached[$row['order_id']];
    }
}
