<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class TransSN implements ColumnInterface
{
    public function getHeader()
    {
        return __('TransSN');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if ($row['item_type'] !== 'item') {
            return '';
        }
        return $row['hotai_point_deduction_point_trans_s_n'];
    }

}
