<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       23/02/2026
 */

namespace Branch8\Shipping\Model;

use Magento\Sales\Model\OrderRepository;

class GetTrackingList
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

    /**
     * @param int $orderId
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    function execute(int $orderId)
    {
        $order = $this->orderRepository->get($orderId);
        $tracks = $order->getTracksCollection();
        $trackingData = [];
        foreach ($tracks as $track) {
            $trackingData[] = TrackingToArray::convert($track);
        }
        return $trackingData;
    }
}
