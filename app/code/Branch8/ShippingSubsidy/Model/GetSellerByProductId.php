<?php

/**
 * Copyright © 2025 Branch8. All rights reserved.
 */

declare(strict_types=1);

namespace Branch8\ShippingSubsidy\Model;

use Magento\Framework\App\ResourceConnection;

class GetSellerByProductId
{
    private array $productToSeller = [];

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @param int $productId
     * @return int
     */
    public function get(int $productId): int
    {
        if (isset($this->productToSeller[$productId])) {
            return $this->productToSeller[$productId];
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('marketplace_product'), ['seller_id'])
            ->where('mageproduct_id = ?', $productId)
            ->limit(1);

        $this->productToSeller[$productId] = (int)$connection->fetchOne($select);
        return $this->productToSeller[$productId];
    }
}
