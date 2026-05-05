<?php

declare(strict_types=1);

namespace Branch8\Report\Model;

use Branch8\Report\Api\Data\ProductChangeLogInterface;

class ProductLogContext
{
    /**
     * @var array
     */
    private array $logs = [];

    /**
     * Stores a change log for a specific product ID.
     *
     * @param int $productId
     * @param ProductChangeLogInterface $log
     *
     * @return void
     */
    public function add(int $productId, ProductChangeLogInterface $log): void
    {
        $this->logs[$productId] = $log;
    }

    /**
     * Retrieves the stored change log for a given product ID.
     *
     * @param int $productId
     * @return ProductChangeLogInterface|null
     */
    public function get(int $productId): ?ProductChangeLogInterface
    {
        return $this->logs[$productId] ?? null;
    }

    /**
     * Clears all stored logs.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->logs = [];
    }
}
