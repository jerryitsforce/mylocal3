<?php

declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportSeller\Model\Columns\Summarize;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\App\ResourceConnection;

class PlatformShare implements ColumnInterface
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * Constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritDoc
     */
    public function getHeader()
    {
        return __('Platform Share');
    }

    /**
     * @inheritDoc
     */
    public function processColumnData(array $row = [])
    {
        if (!empty($row['order_item_ids'])) {
            return $this->getShareCost($row['order_item_ids']);
        }
        return '';
    }

    /**
     * Returns share cost.
     *
     * @param string|array $itemIds
     *
     * @return float
     */
    private function getShareCost($itemIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['soi' => 'sales_order_item'],
                ['total_share_cost' => new \Zend_Db_Expr('SUM(platform_borne_total_amount)')]
            )
            ->where('soi.item_id IN (?)', $itemIds);

        $result = $connection->fetchOne($select);
        return (float)$result;
    }
}
