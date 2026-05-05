<?php

namespace Branch8\TicketApi\Api\Data;

use Branch8\TicketApi\Api\Data\TicketApiBrandInterface as ModelInterface;

interface TicketApiBrandSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return ModelInterface[]
     */
    public function getItems();

    /**
     * @param ModelInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
