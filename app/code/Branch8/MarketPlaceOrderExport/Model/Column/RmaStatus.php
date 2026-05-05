<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class RmaStatus implements ColumnInterface
{
    public function getHeader()
    {
        return __('RMA Status');
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
        return __($row['rma_status']);
    }

}
