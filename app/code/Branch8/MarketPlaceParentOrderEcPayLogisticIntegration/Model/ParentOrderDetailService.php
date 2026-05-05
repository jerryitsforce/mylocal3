<?php
declare(strict_types=1);


namespace Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Model;

use Magento\Framework\App\ResourceConnection;

class ParentOrderDetailService
{
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
     * @param array $data
     * @return void
     */
    public function setDetaiLDataByResource($orderId, array $data): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('sales_parent_order_detail');
        $connection->update($tableName, $data, [
            'parent_id = ?' => $orderId,
        ]);
        $connection->closeConnection();
    }
}
