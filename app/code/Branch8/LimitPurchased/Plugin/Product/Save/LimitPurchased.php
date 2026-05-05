<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\LimitPurchased\Plugin\Product\Save;

use Magento\Catalog\Model\Product;
use Magento\Framework\Indexer\IndexerRegistry;
use Branch8\LimitPurchased\Model\Indexer\LimitPurchased as LimitPurchasedIndexer;

class LimitPurchased
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        IndexerRegistry $indexerRegistry
    ) {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Reindex on product save
     *
     * @param Product $subject
     * @param Product $result
     * @return Product
     */
    public function afterSave(Product $subject, Product $result)
    {
        $indexer = $this->indexerRegistry->get(LimitPurchasedIndexer::INDEX_ID);
        if (!$indexer->isScheduled()) {
            $indexer->reindexRow($result->getId());
        }
        return $result;
    }
}
