<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class ReceiverName implements ColumnInterface
{
    public function getHeader()
    {
        return __('Receiver Name');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if (isset($row['is_flagship_store_process_order']) && $row['is_flagship_store_process_order'] == 1) {
            return $row['receiver_billingname'];
        }
        return $row['receiver_name'];
    }

}
