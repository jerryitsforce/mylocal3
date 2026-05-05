<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model\Services;

use Magento\Framework\App\ResourceConnection;

class GetPurchaseCost
{
    private $cache = [];
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

    /**
     * @param $orderItemIds
     * @return mixed|string
     */
    public function execute($orderItemIds)
    {
        if (isset($this->cache[$orderItemIds])) {
            return $this->cache[$orderItemIds];
        }
        $itemIds = @explode(",", $orderItemIds);;
        $row['purchase_cost'] = '';
        if ($itemIds) {
            $data = $this->getPurchaseCost($itemIds);
            $row['purchase_cost'] = $data['purchase_cost'] ?? '';
        }
        $this->cache[$orderItemIds] = $row['purchase_cost'];
        return $this->cache[$orderItemIds];
    }

    private function getPurchaseCost($itemIds)
    {
        $select = $this->resourceConnection->getConnection()->select();
        $connection = $this->resourceConnection->getConnection();
        $columns = [
            'purchase_cost' => 'ROUND(SUM(COALESCE(sales_order_item.base_cost * sales_order_item.qty_ordered, 0)))',
        ];
        $select->from('sales_order_item', $columns)
            ->where('sales_order_item.item_id in (?)', $itemIds);
        //echo $select;die;
        return $connection->fetchRow($select);
    }
}
