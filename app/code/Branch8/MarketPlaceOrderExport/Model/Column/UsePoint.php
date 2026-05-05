<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;

class UsePoint implements ColumnInterface
{
    private OrderRepository $orderRepository;

    /**
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        OrderRepository $orderRepository
    )
    {
        $this->orderRepository = $orderRepository;
    }

    public function getHeader()
    {
        return __('Used Point');
    }

    public function processColumnData(array $row = [])
    {
        if ($row['item_type'] === 'shipping' || $row['invoice_order_item_name'] === '訂單處理費') {
            return 0;
        }


        return (int)$row['row_total_point_used'];
    }
}
