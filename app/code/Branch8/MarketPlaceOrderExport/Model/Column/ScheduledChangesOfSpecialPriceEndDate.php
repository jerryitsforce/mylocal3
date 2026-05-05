<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class ScheduledChangesOfSpecialPriceEndDate implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('Scheduled Changes Of Special Price End Date');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if (isset($this->cached[$row['order_item_id']])) {
            return $this->cached[$row['order_item_id']];
        }
        $this->cached[$row['order_item_id']] = '';
        if (isset($row['schedule_change_special_price_end'])) {
            $this->cached[$row['order_item_id']] = (new \DateTime(
                $row['schedule_change_special_price_end'],
                new \DateTimeZone('UTC')
            ))->format('d/m/y');
        }
        return $this->cached[$row['order_item_id']];
    }
}
