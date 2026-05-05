<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\LimitPurchased\Model\CacheStorage;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

class CacheProduct implements ResetAfterRequestInterface
{
    /**
     * @var array
     */
    private $cachedItems = [];

    /**
     * @var array
     */
    private $cachedQtyOrdered = [];

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->clean();
    }

    /**
     * Clean storage
     *
     * @return void
     */
    public function clean()
    {
        $this->cachedItems = [];
        $this->cachedQtyOrdered = [];
    }

    /**
     * Save item to cache
     *
     * @param string $sku
     * @param ProductInterface $item
     */
    public function set($sku, ProductInterface $item): void
    {
        $this->cachedItems[$this->normalizeSku($sku)] = $item;
    }

    /**
     * Get item from cache
     *
     * @param string $sku
     * @return ProductInterface
     */
    public function get($sku): ?ProductInterface
    {
        return $this->cachedItems[$this->normalizeSku($sku)] ?? null;
    }

    /**
     * Delete item from cache
     *
     * @param string $sku
     */
    public function delete($sku): void
    {
        unset($this->cachedItems[$this->normalizeSku($sku)]);
    }

    /**
     * Save QtyOrdered to cache
     *
     * @param string $sku
     * @param int $qty
     */
    public function setQtyOrdered($sku, int $qty): void
    {
        $this->cachedQtyOrdered[$this->normalizeSku($sku)] = $qty;
    }

    /**
     * Get item from cache
     *
     * @param string $sku
     * @return int
     */
    public function getQtyOrdered($sku): ?int
    {
        return $this->cachedQtyOrdered[$this->normalizeSku($sku)] ?? 0;
    }

    /**
     * Delete item from cache
     *
     * @param string $sku
     */
    public function deleteQtyOrdered($sku): void
    {
        unset($this->cachedQtyOrdered[$this->normalizeSku($sku)]);
    }

    /**
     * Normalize SKU by converting it to lowercase.
     *
     * @param string $sku
     * @return string
     */
    private function normalizeSku($sku): string
    {
        return mb_convert_case((string)$sku, MB_CASE_LOWER, 'UTF-8');
    }
}
