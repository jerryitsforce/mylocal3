<?php

namespace Branch8\TicketApi\Api\Data;

use Branch8\TicketApi\Api\Data\TicketApiMerchantInterface;

interface TicketApiMerchantSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return TicketApiMerchantInterface[]
     */
    public function getItems();

    /**
     * @param TicketApiMerchantInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
