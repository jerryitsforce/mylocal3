<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Sales\Model\OrderRepository;

class ShippingMethod implements ColumnInterface
{
    private OrderRepository $orderRepository;
    private $cached;

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
        return __('Shipping Method');
    }

    public function processColumnData(array $row = [])
    {
        if (isset($this->cached[$row['order_id']])) {
            return $this->cached[$row['order_id']];
        }
        $this->cached[$row['order_id']] = '';
        $order = $this->orderRepository->get($row['order_id']);
        if ($order->getShippingDescription()) {
            $this->cached[$row['order_id']] = (string)$order->getShippingDescription();
        }
        return $this->cached[$row['order_id']];
    }

}
