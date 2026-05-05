<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\GetOrderRmaDetail;
use Branch8\MarketPlaceOrderExport\Model\Services\GetRmaOrderItemInformation;
use Branch8\Rma\Model\Rma\Source\RmaStatus;
use Magento\Framework\Stdlib\DateTime\Timezone;
use Magento\Store\Model\StoreManagerInterface;
use Branch8\HotaiCore\Model\Order\Status;

class ApplyForReturnTime implements ColumnInterface
{
    private $cached = [];

    private GetOrderRmaDetail $getOrderRmaDetail;
    private Timezone $timeZone;
    private StoreManagerInterface $storeManager;
    /**
     * @var GetRmaOrderItemInformation
     */
    private GetRmaOrderItemInformation $getRmaOrderItemInformation;

    /**
     * @param GetOrderRmaDetail $getOrderRmaDetail
     * @param StoreManagerInterface $storeManager
     * @param Timezone $timezone
     * @param GetRmaOrderItemInformation $getRmaOrderItemInformation
     */
    public function __construct(
        GetOrderRmaDetail          $getOrderRmaDetail,
        StoreManagerInterface      $storeManager,
        Timezone                   $timezone,
        GetRmaOrderItemInformation $getRmaOrderItemInformation
    )
    {
        $this->storeManager = $storeManager;
        $this->timeZone = $timezone;
        $this->getOrderRmaDetail = $getOrderRmaDetail;
        $this->getRmaOrderItemInformation = $getRmaOrderItemInformation;
    }

    public function getHeader()
    {
        return __('Apply for return time');
    }

    /**
     * @param array $row
     * @return int|\Magento\Framework\Phrase|string
     */
    public function processColumnData(array $row = [])
    {
        $data = $this->getRmaOrderItemInformation->get($row['order_item_id'], Status::STATUS_APPLYING_RETURN);
        if ($data && isset($data['date_add']) && $data['date_add']) {
            return $data['date_add'];
        }
        return '';
    }
}
