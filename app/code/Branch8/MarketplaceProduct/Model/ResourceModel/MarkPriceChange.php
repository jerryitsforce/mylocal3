<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;

class MarkPriceChange
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * MarkPriceChange constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Mark the product price in quote as changed.
     *
     * @param array $productIds
     *
     * @return void
     */
    public function execute(array $productIds): void
    {
        $connection = $this->resourceConnection->getConnection();

        $tableQuote = $connection->getTableName('quote');
        $tableItem = $connection->getTableName('quote_item');
        $subSelect = $connection->select()
            ->from($tableItem, ['entity_id' => 'quote_id'])
            ->where('product_id IN ( ? )', $productIds)
            ->where('available_to_checkout = ?', 1)
            ->group('quote_id');
        $select = $connection->select()
            ->join(
                ['t2' => $subSelect],
                't1.entity_id = t2.entity_id',
                ['is_price_changed' => new Expression('1')]
            );
        $updateQuery = $select->crossUpdateFromSelect(['t1' => $tableQuote]);
        $connection->query($updateQuery);
    }
}
