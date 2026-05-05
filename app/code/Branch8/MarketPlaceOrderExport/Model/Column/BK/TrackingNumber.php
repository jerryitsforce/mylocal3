<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\App\ResourceConnection;

class TrackingNumber implements ColumnInterface
{

    private $cached;

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
        return __('Tracking Number');
    }

    public function processColumnData(array $row = [])
    {
        if (isset($this->cached[$row['order_item_id']])) {
            return $this->cached[$row['order_item_id']];
        }
        $this->cached[$row['order_item_id']] = '';
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $track = $this->getTrackingNumberForItem($row['order_item_id']);
        $this->cached[$row['order_item_id']] = $track ? $track['track'] : '';
        return $this->cached[$row['order_item_id']];
    }

    /**
     * @param $itemId
     * @return string
     */
    private function getTrackingNumberForItem($itemId)
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from('sales_shipment_item', [])
            ->join(
                'sales_shipment_track',
                'sales_shipment_item.parent_id = sales_shipment_track.parent_id',
                ['track' => new \Zend_Db_Expr('group_concat(`track_number`)')]
            )->where('order_item_id = ? ', $itemId)->group('order_item_id');
        return $this->resourceConnection->getConnection()->fetchRow($select);
    }
}
