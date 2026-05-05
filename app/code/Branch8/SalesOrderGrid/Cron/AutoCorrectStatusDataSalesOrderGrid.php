<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types=1);

namespace Branch8\SalesOrderGrid\Cron;
use Magento\Framework\App\ResourceConnection;

class AutoCorrectStatusDataSalesOrderGrid
{
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
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        /**
         * @TODO  optimize this query cost 4s to return result
         */
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from('sales_order as so', ['total' => new \Zend_Db_Expr('COUNT(*)')])
            ->join('sales_order_grid as sg', 'so.entity_id = sg.entity_id')
            ->where('so.status <> sg.status');
        $total = (int)$this->resourceConnection->getConnection()->fetchRow($select)['total'];
        if ($total === 0) {
            return;
        }
        $query = 'UPDATE sales_order_grid AS grid
JOIN sales_order AS so ON grid.entity_id = so.entity_id
SET grid.status = so.status
WHERE grid.status <> so.status';
        $this->resourceConnection->getConnection()->query($query);
    }


    private function getQuery($isCount = false)
    {
        $this->resourceConnection->getConnection()->select()
            ->from('sales_order as so')
            ->join('sales_order_grid as sg', 'so.entity_id = sg.entity_id')
            ->where('so.status <> sg.status');
    }
}
