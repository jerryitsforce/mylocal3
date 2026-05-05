<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\GetRmaOrderItemInformation;
use Branch8\Rma\Model\Rma\DeliveryTime;

class ScheduledReturnTime implements ColumnInterface
{
    private $cached = [];
    private GetRmaOrderItemInformation $getRmaOrderItemInformation;

    /**
     * @param GetRmaOrderItemInformation $getRmaOrderItemInformation
     * @return void
     */
    public function __construct(GetRmaOrderItemInformation $getRmaOrderItemInformation)
    {
        $this->getRmaOrderItemInformation = $getRmaOrderItemInformation;
    }

    public function getHeader()
    {
        return __('Scheduled Return Time');
    }

    /**
     * @param array $row
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processColumnData(array $row = [])
    {
        $isReturnFlow = in_array($row['flow_status'], Status::RETURN_FLOW);
        if (!$isReturnFlow) {
            return '';
        }
        $row = $this->getRmaOrderItemInformation->get($row['order_item_id']);
        if ($row && isset($row['rma_delivery_time']) && $row['rma_delivery_time']) {
            return $this->getDeliveryText($row['rma_delivery_time']);
        }
        return '';
    }

    /**
     * @param $deliveryTime
     * @return string
     */
    private function getDeliveryText($deliveryTime)
    {
        $times = explode(',', $deliveryTime);
        $timeText = [];
        foreach ($times as $time) {
            if ($text = DeliveryTime::getOptionTextById($time)) {
                $timeText[] = trim($text);
            }
        }
        return join(',', $timeText);
    }
}
