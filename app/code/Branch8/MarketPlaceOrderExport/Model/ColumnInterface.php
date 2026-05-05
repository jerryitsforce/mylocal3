<?php

namespace Branch8\MarketPlaceOrderExport\Model;

interface ColumnInterface
{
    /**
     * @return string
     */
    public function getHeader();

    /**
     * @param array $row
     * @return string|int
     */
    public function processColumnData(array $row = []);
}
