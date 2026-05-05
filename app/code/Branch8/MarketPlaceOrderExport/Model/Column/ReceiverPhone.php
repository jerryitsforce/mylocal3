<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class ReceiverPhone implements ColumnInterface
{
    public function getHeader()
    {
        return __('Receiver Phone');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if (isset($row['is_flagship_store_process_order']) && $row['is_flagship_store_process_order'] == 1) {
            return $row['receiver_billingphone'];
        }
        return $row['receiver_phone'];
    }

}
