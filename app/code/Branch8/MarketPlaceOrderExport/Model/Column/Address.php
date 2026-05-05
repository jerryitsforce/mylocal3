<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class Address implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('Address');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if (isset($this->cached[$row['order_id']])) {
            return $this->cached[$row['order_id']];
        }
        $this->cached[$row['order_id']] = '';
        if (empty($row['street'])) {
            return $this->cached[$row['order_id']];
        }
        $this->cached[$row['order_id']] = $row['street'];
        try {
            $postcode = (empty($row['postcode'])) ? '' : $row['postcode']." - ";
            $text = $postcode . $row['region'] . ' ' . $row['city'] . ' ' . $row['street'];
            $this->cached[$row['order_id']] = trim($text);
        } catch (\Exception $exception) {

        }
        return $this->cached[$row['order_id']];
    }
}
