<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;

class ShippingName implements ColumnInterface
{
    private $cached = [];
    /**
     * @var OrderRepository
     */
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
        return __('Name');
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
        $name = $row['name'];
        $shippingName = $this->getShippingName($row['order_id']);
        $this->cached[$row['order_id']] = $shippingName ?: $name;
        return $this->cached[$row['order_id']];
    }

    /**
     * @param $orderId
     * @return string
     */
    private function getShippingName($orderId)
    {
        try {
            /**
             * @var $order \Magento\Sales\Model\Order
             */
            $order = $this->orderRepository->get($orderId);
            if ($address = $order->getShippingAddress()) {
                return $address->getFirstname();
            }
        } catch (NoSuchEntityException $e) {
            return '';
        }
    }

}
