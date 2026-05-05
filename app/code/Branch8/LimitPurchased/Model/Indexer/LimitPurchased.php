<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\LimitPurchased\Model\Indexer;

use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class LimitPurchased implements IndexerActionInterface, MviewActionInterface
{
    public const INDEX_TABLE = 'branch8_limit_purchased_index';

    public const INDEX_ID = 'branch8_limit_purchased';

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @param ResourceConnection $resource
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(
        ResourceConnection $resource,
        CollectionFactory $productCollectionFactory
    ) {
        $this->resource = $resource;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @return void
     */
    public function execute($ids)
    {
        $this->executeList($ids);
    }

    /**
     * Execute full indexation
     *
     * @return void
     */
    public function executeFull()
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName(self::INDEX_TABLE);
        $connection->truncateTable($tableName);

        $this->executeList([]);
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     * @return void
     */
    public function executeList(array $ids)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect([
            'limit_purchased_enable',
            'limit_purchased_start_time',
            'limit_purchased_end_time',
            'limit_purchased_customer_group',
            'limit_purchased_qty'
        ]);

        if (!empty($ids)) {
            $collection->addIdFilter($ids);
        } else {
            $collection->addAttributeToFilter('limit_purchased_enable', 1);
        }

        $data = [];
        $deleteIds = [];
        $foundIds = [];

        foreach ($collection as $product) {
            $foundIds[] = $product->getId();
            if ($product->getLimitPurchasedEnable()) {
                $data[] = [
                    'product_id' => (int)$product->getId(),
                    'is_enabled' => (bool)$product->getLimitPurchasedEnable(),
                    'start_time' => $product->getLimitPurchasedStartTime(),
                    'end_time' => $product->getLimitPurchasedEndTime(),
                    'customer_group_ids' => $product->getLimitPurchasedCustomerGroup(),
                    'limit_qty' => $product->getLimitPurchasedQty()
                ];
            } else {
                $deleteIds[] = $product->getId();
            }
        }

        if (!empty($ids)) {
            $missingIds = array_diff($ids, $foundIds);
            $deleteIds = array_merge($deleteIds, $missingIds);
        }

        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName(self::INDEX_TABLE);

        if (!empty($deleteIds)) {
            $connection->delete($tableName, ['product_id IN (?)' => $deleteIds]);
        }

        if (!empty($data)) {
            $connection->insertOnDuplicate(
                $tableName,
                $data,
                ['is_enabled', 'start_time', 'end_time', 'customer_group_ids', 'limit_qty']
            );
        }
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     * @return void
     */
    public function executeRow($id)
    {
        $this->executeList([$id]);
    }
}
