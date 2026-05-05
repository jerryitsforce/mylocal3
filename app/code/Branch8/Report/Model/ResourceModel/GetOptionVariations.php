<?php

declare(strict_types=1);

namespace Branch8\Report\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

class GetOptionVariations
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
     * Returns option's variations.
     *
     * @param int $productId
     * @param mixed $cols
     *
     * @return array
     */
    public function execute(int $productId, mixed $cols = '*'): array
    {
        $connection = $this->resourceConnection->getConnection();

        $tableName = $connection->getTableName('wk_osi_variations');
        $select = $connection->select()
            ->from($tableName, $cols)
            ->where('product_id = ?', $productId);

        $data = $connection->fetchAll($select);
        return (array)$data ?: [];
    }
}
