<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\GetOrderRmaDetail;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Store\Model\StoreManagerInterface;

class OrderReturnTime implements ColumnInterface
{
    private $cached = [];

    private GetOrderRmaDetail $getOrderRmaDetail;
    private Timezone $timeZone;
    private StoreManagerInterface $storeManager;

    /**
     * @param GetOrderRmaDetail $getOrderRmaDetail
     * @param StoreManagerInterface $storeManager
     * @param Timezone $timezone
     */
    public function __construct(
        GetOrderRmaDetail     $getOrderRmaDetail,
        StoreManagerInterface $storeManager,
        Timezone              $timezone
    )
    {
        $this->storeManager = $storeManager;
        $this->timeZone = $timezone;
        $this->getOrderRmaDetail = $getOrderRmaDetail;
    }

    public function getHeader()
    {
        return __('Order Return Time');
    }

    /**
     * @param array $row
     * @return int|string
     * @throws \DateMalformedStringException
     */
    public function processColumnData(array $row = [])
    {
        $row = $this->getOrderRmaDetail->get($row['order_id'], $row['order_item_id']);
        if ($row && isset($row['created_date']) && $row['created_date']) {
            $timezone = new \DateTimeZone($this->timeZone->getConfigTimezone());
            return (new \DateTime($row['created_date'], $timezone))->format('m/d/Y');
        }
        return '';
    }
}
