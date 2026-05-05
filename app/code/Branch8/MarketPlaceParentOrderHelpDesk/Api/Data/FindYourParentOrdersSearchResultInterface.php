<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderHelpDesk\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Find Your Order Search result
 */
interface FindYourParentOrdersSearchResultInterface extends SearchResultsInterface
{
    /**
     * Gets collection items.
     *
     * @return \Branch8\MarketPlaceParentOrderHelpDesk\Api\Data\FindYourParentOrdersDataInterface[] Array of collection items.
     */
    public function getItems();

    /**
     * Sets collection items.
     *
     * @param \Branch8\MarketPlaceParentOrderHelpDesk\Api\Data\FindYourParentOrdersDataInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
