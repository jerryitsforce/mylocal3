<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\GetRmaOrderItemInformation;

class ReturnResult implements ColumnInterface
{

    public function getHeader()
    {
        return __('Return Result');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if ($row['flow_status'] === Status::STATUS_RETURNED) {
            return $row['flow_status'];
        }
        return '';
    }
}
