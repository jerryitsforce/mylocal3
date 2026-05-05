<?php

namespace Branch8\RmaAdminUi\Model\Actions;

use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Magento\Framework\App\ResourceConnection;


class TotalRmaInReviews
{
    private $resourceConnection;

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
     * @return int
     */
    public function execute()
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $marketPlaceRmaDetail = $connection->getTableName('marketplace_rma_details');
        $where = ['status' => RmaStatus::RETURN_FINANCIAL_REVIEW_PROCESSING];
        $select->from(['marketplace_rma_details' => $marketPlaceRmaDetail], new \Zend_Db_Expr('COUNT(*)'))
            ->where('status in (?)', $where);
        return (int)$connection->fetchOne($select);
    }
}
