<?php

declare(strict_types=1);

namespace Branch8\Catalog\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class ProductCopyTracking
{
    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * Constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    private function getMainTable(): string
    {
        return $this->connection->getTableName('catalog_product_copy_tracking');
    }

    /**
     * Check if the product is tracked in the table.
     *
     * @param int $productId
     *
     * @return bool
     */
    public function isTracked(int $productId): bool
    {
        $select = $this->connection->select()
            ->from($this->getMainTable(), 'product_id')
            ->where('product_id = ?', $productId)
            ->where('has_draft = 0')
            ->limit(1);

        return (bool)$this->connection->fetchOne($select);
    }

    /**
     * Mark the product as duplicated.
     *
     * @param int $productId
     *
     * @return void
     */
    public function insert(int $productId): void
    {
        $this->connection->insertOnDuplicate($this->getMainTable(), ['product_id' => $productId]);
    }

    /**
     * Marks a product as having a draft.
     *
     * @param int $productId
     *
     * @return void
     */
    public function markAsDraft(int $productId): void
    {
        $bind = ['has_draft' => 1];
        $where = ['product_id = ?' => $productId];

        $this->connection->update($this->getMainTable(), $bind, $where);
    }

    /**
     * Remove the product from the duplicated list.
     *
     * @param int $productId
     *
     * @return void
     */
    public function delete(int $productId): void
    {
        $this->connection->delete($this->getMainTable(), ['product_id = ?' => $productId]);
    }
}
