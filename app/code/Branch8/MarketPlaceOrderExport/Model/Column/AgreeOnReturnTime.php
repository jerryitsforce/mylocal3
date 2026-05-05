<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\DateFormat;
use Branch8\MarketPlaceOrderExport\Model\Services\GetAllRmaStatusItem;

class AgreeOnReturnTime implements ColumnInterface
{
    private $cached = [];

    private GetAllRmaStatusItem $getAllRmaStatusItem;

    /**
     * @param GetAllRmaStatusItem $getAllRmaStatusItem
     */
    public function __construct(
        GetAllRmaStatusItem                    $getAllRmaStatusItem,
    )
    {
        $this->getAllRmaStatusItem = $getAllRmaStatusItem;
    }

    public function getHeader()
    {
        return __('Return Approval Time');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if (!in_array($row['flow_status'], Status::RETURN_FLOW)) {
            return '';
        }
        $rmaStatus = $this->getAllRmaStatusItem->get($row['order_id'], $row['item_id'], $row['rma_options']);
        if (isset($rmaStatus[Status::STATUS_APPLYING_RETURN])) {
            return DateFormat::getChangeDayTitle($rmaStatus[Status::STATUS_APPLYING_RETURN],true);
        }
        return '';
    }
}
