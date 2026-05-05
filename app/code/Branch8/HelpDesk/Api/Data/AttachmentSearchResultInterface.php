<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for Result Attachment Search
 */
interface AttachmentSearchResultInterface extends SearchResultsInterface
{
    /**
     * Gets collection items.
     *
     * @return \Branch8\HelpDesk\Api\Data\AttachmentInterface[] Array of collection items.
     */
    public function getItems();

    /**
     * Sets collection items.
     *
     * @param \Branch8\HelpDesk\Api\Data\AttachmentInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
