<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Rma\Api\Data;

interface MarketplaceRmaShippingSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get MarketplaceRmaShipping list.
     * @return \Branch8\Rma\Api\Data\MarketplaceRmaShippingInterface[]
     */
    public function getItems();

    /**
     * Set parent_id list.
     * @param \Branch8\Rma\Api\Data\MarketplaceRmaShippingInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

