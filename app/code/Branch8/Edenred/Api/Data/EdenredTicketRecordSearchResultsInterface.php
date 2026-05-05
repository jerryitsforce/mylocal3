<?php

namespace Branch8\Edenred\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface EdenredTicketRecordSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \Branch8\Edenred\Api\Data\EdenredTicketRecordInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\Edenred\Api\Data\EdenredTicketRecordInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
