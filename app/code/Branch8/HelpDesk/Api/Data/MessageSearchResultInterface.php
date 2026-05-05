<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Message Search Result Interface
 */
interface MessageSearchResultInterface extends SearchResultsInterface
{
    /**
     * Gets collection items.
     *
     * @return \Branch8\HelpDesk\Api\Data\LessDataMessageInterface[] Array of collection items.
     */
    public function getItems();

    /**
     * Sets collection items.
     *
     * @param \Branch8\HelpDesk\Api\Data\LessDataMessageInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
