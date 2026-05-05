<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\DateFormat;
use Branch8\MarketPlaceOrderExport\Model\Services\GetOrderCancelInformation;
use Branch8\MarketPlaceOrderExport\Model\Services\GetParentOrderCancelInformation;

class OrderCancellationTime implements ColumnInterface
{
    private $cached = [];
    private GetOrderCancelInformation $getOrderCancelInformation;

    private GetParentOrderCancelInformation $getParentOrderCancelInformation;

    /**
     * @param GetOrderCancelInformation $getOrderCancelInformation
     * @param GetParentOrderCancelInformation $getParentOrderCancelInformation
     */
    public function __construct(
        GetOrderCancelInformation       $getOrderCancelInformation,
        GetParentOrderCancelInformation $getParentOrderCancelInformation
    )
    {
        $this->getOrderCancelInformation = $getOrderCancelInformation;
        $this->getParentOrderCancelInformation = $getParentOrderCancelInformation;
    }

    public function getHeader()
    {
        return __('Order Cancellation Time');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processColumnData(array $row = [])
    {
        if (isset($this->cached[$row['order_id']])) {
            return $this->cached[$row['order_id']];
        }
        if ($row['order_status'] !== 'canceled') {
            $this->cached[$row['order_id']] = '';
        } else {
            $this->cached[$row['order_id']] = DateFormat::getChangeDayTitle($row['order_updated_at'],true);
            if (!empty($row['parent_order_id']) && $cancelInformation = $this->getParentOrderCancelInformation->get($row['parent_order_id'])) {
                $this->cached[$row['order_id']] = $cancelInformation['date_add'];
            }
        }
        return $this->cached[$row['order_id']];
    }
}
