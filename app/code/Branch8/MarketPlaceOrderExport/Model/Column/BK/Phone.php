<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\Customer\Model\ResourceModel\CustomerRepository;
use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;

class Phone implements ColumnInterface
{
    private OrderRepository $orderRepository;

    private $cached;

    /**
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        CustomerRepository $repository,
        OrderRepository    $orderRepository
    )
    {
        $this->orderRepository = $orderRepository;
    }

    public function getHeader()
    {
        return __('Phone');
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
        $this->cached[$row['order_id']] = $this->getPhone($row['order_id']);
        return $this->cached[$row['order_id']];
    }

    private function getPhone($orderId)
    {
        try {
            /**
             * @var $order \Magento\Sales\Model\Order
             */
            $order = $this->orderRepository->get($orderId);
            if (($shippingAddress = $order->getShippingAddress()) && $shippingAddress->getTelephone()) {
                return $shippingAddress->getTelephone();
            }
            if (($billingAddress = $order->getBillingAddress()) && $billingAddress->getTelephone()) {
                return $billingAddress->getTelephone();
            }
            return '';
        } catch (NoSuchEntityException $e) {
            return '';
        }
    }
}
