<?php

namespace Branch8\Catalog\Model\Actions;

use Magento\Framework\App\ResourceConnection;

class GetTotalSale
{
    private ResourceConnection $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource,
    )
    {
        $this->resource = $resource;
    }

    /**
     * @return string
     */
    public function get($productId)
    {
        //sales_bestsellers_aggregated_daily
        $connection = $this->resource->getConnection();
        $select = $connection->select();
        $select->from(
            ['sales_bestsellers_aggregated_daily' =>
                $connection->getTableName('sales_bestsellers_aggregated_daily')],
            ['SUM(sales_bestsellers_aggregated_daily.qty_ordered) as ordered_qty']
        )->where('product_id = ?', $productId);
        return (int)$connection->fetchOne($select);
    }
}
