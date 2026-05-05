<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class FlowStatus implements ColumnInterface
{
    public function getHeader()
    {
        return __('Flow Status');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        return !empty($row['flow_status']) ? __($row['flow_status']) : "";
    }

}
