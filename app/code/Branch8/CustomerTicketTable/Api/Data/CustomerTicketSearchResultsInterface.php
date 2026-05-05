<?php

namespace Branch8\CustomerTicketTable\Api\Data;

interface CustomerTicketSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \Branch8\CustomerTicketTable\Api\Data\CustomerTicketInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\CustomerTicketTable\Api\Data\CustomerTicketInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
