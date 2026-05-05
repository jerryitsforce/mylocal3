<?php

declare(strict_types=1);

namespace Branch8\Catalog\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Store\Model\Store;

class GetProductNameStatusByIds
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;

    /**
     * Constructor.
     *
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Get name and status of products by product IDs.
     *
     * @param array $productIds
     *
     * @return array
     */
    public function execute(array $productIds): array
    {
        $connection = $this->resource->getConnection();

        $eavAttributeTable = $this->resource->getTableName('eav_attribute');

        $selectStatusAttrId = $connection->select()
            ->from($eavAttributeTable, ['attribute_id'])
            ->where('attribute_code = ?', ProductInterface::STATUS)
            ->where('entity_type_id = ?', 4);
        $statusAttrId = $connection->fetchOne($selectStatusAttrId);

        $selectNameAttrId = $connection->select()
            ->from($eavAttributeTable, ['attribute_id'])
            ->where('attribute_code = ?', 'name')
            ->where('entity_type_id = ?', 4);
        $nameAttrId = $connection->fetchOne($selectNameAttrId);

        if (!$statusAttrId || !$nameAttrId) {
            return [];
        }

        $eavIntTable = $connection->getTableName('catalog_product_entity_int');
        $eavVarcharTable = $connection->getTableName('catalog_product_entity_varchar');
        $productTable = $connection->getTableName('catalog_product_entity');

        $select = $connection->select()
            ->from(['e' => $eavIntTable], [
                'product_id' => 'p.entity_id',
                'status' => 'e.value',
                'product_label' => new Expression("CONCAT(p.entity_id, ' - ', v.value)")
            ])
            ->join(['p' => $productTable], 'e.row_id = p.row_id', [])
            ->join(
                ['v' => $eavVarcharTable],
                'v.row_id = p.row_id AND v.attribute_id = ' . (int)$nameAttrId . ' AND v.store_id = ' . Store::DEFAULT_STORE_ID,
                []
            )
            ->where('e.attribute_id = ?', $statusAttrId)
            ->where('p.entity_id IN (?)', $productIds)
            ->where('e.store_id = ?', Store::DEFAULT_STORE_ID);

        return $connection->fetchAll($select);
    }
}
