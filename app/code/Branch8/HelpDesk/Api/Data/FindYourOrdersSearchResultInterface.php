<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Find Your Order Search result
 */
interface FindYourOrdersSearchResultInterface extends SearchResultsInterface
{
    /**
     * Gets collection items.
     *
     * @return \Branch8\HelpDesk\Api\Data\FindYourOrdersDataInterface[] Array of collection items.
     */
    public function getItems();

    /**
     * Sets collection items.
     *
     * @param \Branch8\HelpDesk\Api\Data\FindYourOrdersDataInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
