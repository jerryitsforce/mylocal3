<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Sales\Model\OrderRepository;

class Discount implements ColumnInterface
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
        return __('Discount');
    }

    public function processColumnData(array $row = [])
    {
        if ($row['item_type'] === 'shipping' || $row['invoice_order_item_name'] === '訂單處理費') {
            return '';
        }
        return (int)$row['discount_amount'];
    }
}
