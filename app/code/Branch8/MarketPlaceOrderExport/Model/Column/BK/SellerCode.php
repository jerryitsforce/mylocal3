<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Branch8\MarketPlaceOrderExport\Model\Services\GetSellerDataByOrder;

class SellerCode implements ColumnInterface
{
    private $cached = [];
    /**
     * @var GetSellerDataByOrder
     */
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
        return __('Seller Code');
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
        $sellerData = $this->getSellerDataByOrder->get($row['order_id']);
        $this->cached[$row['order_id']] = $sellerData['seller_code'];
        return $this->cached[$row['order_id']];
    }

}
