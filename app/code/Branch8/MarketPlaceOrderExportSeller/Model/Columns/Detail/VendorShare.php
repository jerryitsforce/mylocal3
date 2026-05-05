<?php

declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportSeller\Model\Columns\Detail;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\App\ResourceConnection;

class VendorShare implements ColumnInterface
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
        return __('Vendor Share');
    }

    /**
     * @inheritDoc
     */
    public function processColumnData(array $row = [])
    {
        if (!empty($row['order_id'])) {
            return $this->getShareCost($row['order_id']);
        }
        return '';
    }

    /**
     * Returns share cost.
     *
     * @param int $orderId
     *
     * @return float
     */
    private function getShareCost($orderId)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['so' => 'sales_order'],
                ['total_share_cost' => new \Zend_Db_Expr('SUM(seller_borne_total_amount)')]
            )
            ->where('so.entity_id IN (?)', $orderId);

        $result = $connection->fetchOne($select);
        return (float)$result;
    }
}
