<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\GetRmaOrderItemInformation;
use Branch8\HotaiCore\Model\Order\Status;
class ConfirmReturnResultTime implements ColumnInterface
{
    private GetRmaOrderItemInformation $getRmaOrderItemInformation;

    /**
     * @param GetRmaOrderItemInformation $getRmaOrderItemInformation
     * @return void
     */
    public function __construct(GetRmaOrderItemInformation $getRmaOrderItemInformation)
    {
        $this->getRmaOrderItemInformation=$getRmaOrderItemInformation;
    }

    public function getHeader()
    {
        return __('Confirm Return Result Time');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        $row = $this->getRmaOrderItemInformation->get($row['order_item_id'], Status::STATUS_CLOSED);
        if ($row && isset($row['date_add']) && $row['date_add']) {
            return $row['date_add'];
        }
        return '';
    }
}
