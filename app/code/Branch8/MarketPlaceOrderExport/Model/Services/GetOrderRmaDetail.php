<?php

namespace Branch8\MarketPlaceOrderExport\Model\Services;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManager;

class GetOrderRmaDetail
{
    private $cached = [];
    public ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    )
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $orderId
     * @param $itemId
     * @return mixed
     */
    public function get($orderId, $itemId)
    {
        $key = $orderId . '-' . $itemId;
        if (isset($this->cached[$key])) {
            return $this->cached[$key];
        }
        $select = $this->resourceConnection->getConnection()->select();
        $select->from('marketplace_rma_items', [])
            ->join('marketplace_rma_details',
                'marketplace_rma_items.rma_id=marketplace_rma_details.id',
                [
                    'created_date',
                    'resolution_type',
                    'rma_delivery_time', ''
                ]
            )->where('order_id = ?', $orderId)->where('item_id = ?', $itemId);
        $row = $this->resourceConnection->getConnection()->fetchRow($select);
        $this->cached[$key] = $row;
        return $this->cached[$key];
    }
}
