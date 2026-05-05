<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockIndexer\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\StateException;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSkuListInStock;
use Magento\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;
use Branch8\OptionsWithStockIndexer\Helper\Logger as LoggerInterface;

/**
 * Reindex product options
 */
class SourceItemIndexerPlugin
{

    private ResourceConnection $resouceConnection;
    /**
     * @var GetSkuListInStock
     */
    private $getSkuListInStock;

    private \Magento\Indexer\Model\IndexerFactory $indexerFactory;

    private LoggerInterface $logger;

    /**
     * @param \Magento\Indexer\Model\IndexerFactory $indexerFactory
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        LoggerInterface                       $logger,
        ResourceConnection                    $resourceConnection
    )
    {
        $this->logger = $logger;
        $this->indexerFactory = $indexerFactory;
        $this->resouceConnection = $resourceConnection;
    }

    /**
     * @param Sync $subject
     * @param $result
     * @param array $sourceItemIds
     * @return void
     */
    public function afterExecuteList(
        Sync  $subject,
              $result,
        array $sourceItemIds
    )
    {
        $select = $this->resouceConnection->getConnection()->select();
        $select->from(['i' => 'inventory_source_item'],[])->join(
            ['e' => 'catalog_product_entity',[]],
            'e.sku = i.sku',
        )->where('i.source_item_id IN (?)', $sourceItemIds)->columns(
            ['product_id' => 'e.entity_id']
        );
        $rows = $this->resouceConnection->getConnection()->fetchCol($select);
        if ($rows) {
            $this->logger->info(sprintf('Index branch8_options_stock_index for PRODUCTIDS : %s', join(', ', $rows)));
            $this->indexerFactory->create()->load('branch8_options_stock_index')
                ->reindexList($rows);
        }
    }
}
