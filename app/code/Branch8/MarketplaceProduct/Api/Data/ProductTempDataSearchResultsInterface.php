<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Api\Data;

interface ProductTempDataSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get list of product version.
     *
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface[]
     */
    public function getItems(): array;

    /**
     * Sets list of product version.
     *
     * @param \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items): self;
}
