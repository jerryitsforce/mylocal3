<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Api\Data;

interface ProductVersionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get list of product version.
     *
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface[]
     */
    public function getItems(): array;

    /**
     * Sets list of product version.
     *
     * @param \Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items): self;
}
