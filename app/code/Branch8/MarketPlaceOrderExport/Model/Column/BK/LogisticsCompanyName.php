<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\App\ResourceConnection;

class LogisticsCompanyName implements ColumnInterface
{
    private $cached = [];

    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    )
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function getHeader()
    {
        return __('Logistics Company Name');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if (isset($this->cached[$row['order_item_id']])) {
            return $this->cached[$row['order_item_id']];
        }
        $title = $this->getCarrier($row['order_item_id']);
        $this->cached[$row['order_item_id']] = $title ? $title['carrier_title'] : '';
        return $this->cached[$row['order_item_id']];
    }

    /**
     * @param $itemId
     * @return mixed
     */
    private function getCarrier($itemId)
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from('sales_shipment_item', [])
            ->join(
                'sales_shipment_track',
                'sales_shipment_item.parent_id = sales_shipment_track.parent_id',
                ['carrier_title' => new \Zend_Db_Expr('group_concat(`title`)')]
            )->where('order_item_id = ? ', $itemId)->group('order_item_id');
        return $this->resourceConnection->getConnection()->fetchRow($select);
    }
}
