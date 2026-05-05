<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class IsReverse implements ColumnInterface
{
    public function getHeader()
    {
        return __('Is Reverse');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        return isset($row['is_reverse']) && $row['is_reverse'] == 1 ? 'TRUE' : '';
    }

}
