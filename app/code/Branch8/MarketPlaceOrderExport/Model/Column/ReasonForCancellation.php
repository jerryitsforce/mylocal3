<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\GetOrderCancelInformation;
use Branch8\MarketPlaceOrderExport\Model\Services\GetParentOrderCancelInformation;
use Magento\Sales\Model\OrderRepository;

class ReasonForCancellation implements ColumnInterface
{
    private $cached = [];
    /**
     * @var GetOrderCancelInformation
     */
    private GetOrderCancelInformation $getOrderCancelInformation;
    /**
     * @var GetParentOrderCancelInformation
     */
    private GetParentOrderCancelInformation $getParentOrderCancelInformation;
    private OrderRepository $orderRepository;

    /**
     * @param GetOrderCancelInformation $getOrderCancelInformation
     * @param GetParentOrderCancelInformation $getParentOrderCancelInformation
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        GetOrderCancelInformation       $getOrderCancelInformation,
        GetParentOrderCancelInformation $getParentOrderCancelInformation,
        OrderRepository                 $orderRepository

    )
    {
        $this->orderRepository = $orderRepository;
        $this->getParentOrderCancelInformation = $getParentOrderCancelInformation;
        $this->getOrderCancelInformation = $getOrderCancelInformation;
    }

    public function getHeader()
    {
        return __('Reason For Cancellation');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processColumnData(array $row = [])
    {
        /**
         * temporary return '' follow @karen Request
         * need 3 cases
         *  - Seller Cancelled
         *  - Unpaid Cancellation
         *  - Customer Cancellation
         */
        return '';
        /*if (isset($this->cached[$row['order_id']])) {
            return $this->cached[$row['order_id']];
        }
        if ($row['order_status'] !== 'canceled') {
            return '';
        }
        $this->cached[$row['order_id']] = '';
        $order = $this->orderRepository->get($row['order_id']);
        if (!$order->getTotalPaid()) {
            $this->cached[$row['order_id']] = __('Unpaid Cancellation');
        } else {
            $this->cached[$row['order_id']] = __('Seller Cancelled');

        }
        return $this->cached[$row['order_id']];*/
    }
}
