<?php

declare(strict_types=1);

namespace Branch8\MarketplaceStaging\Api\Data;

interface ProductVersionDataSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get list of product version.
     *
     * @return \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface[]
     */
    public function getItems(): array;

    /**
     * Sets list of product version.
     *
     * @param \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items): self;
}
