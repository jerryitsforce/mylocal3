<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class ScheduledChangesOfSpecialPrice implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('Scheduled Changes Of Special Price');
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
        $this->cached[$row['order_id']] = 'NEED_CONFIRM';
        return $this->cached[$row['order_id']];
    }
}
