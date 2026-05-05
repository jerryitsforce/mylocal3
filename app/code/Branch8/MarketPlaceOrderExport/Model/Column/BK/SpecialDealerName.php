<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\GetSellerDataByOrder;

class SpecialDealerName implements ColumnInterface
{
    private $cached = [];

    private GetSellerDataByOrder $getSellerDataByOrder;

    /**
     * @param GetSellerDataByOrder $getSellerDataByOrder
     */
    public function __construct(
        GetSellerDataByOrder $getSellerDataByOrder
    )
    {
        $this->getSellerDataByOrder = $getSellerDataByOrder;
    }

    public function getHeader()
    {
        return __('Seller Name');
    }

    public function processColumnData(array $row = [])
    {
        if (isset($this->cached[$row['order_id']])) {
            return $this->cached[$row['order_id']];
        }
        $sellerData = $this->getSellerDataByOrder->get($row['order_id']);
        $this->cached[$row['order_id']] = $sellerData['special_dealer_name'];
        return $this->cached[$row['order_id']];
    }

    /**
     * @param $order_id
     * @return mixed
     */
    public function extracted($order_id)
    {
        return $order_id;
    }

}
