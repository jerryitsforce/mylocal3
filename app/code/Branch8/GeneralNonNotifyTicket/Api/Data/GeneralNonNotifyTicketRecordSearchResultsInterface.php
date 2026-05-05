<?php

namespace Branch8\GeneralNonNotifyTicket\Api\Data;

interface GeneralNonNotifyTicketRecordSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \Branch8\GeneralNonNotifyTicket\Api\Data\GeneralNonNotifyTicketRecordInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\GeneralNonNotifyTicket\Api\Data\GeneralNonNotifyTicketRecordInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
