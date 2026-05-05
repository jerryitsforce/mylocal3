<?php

namespace Branch8\GeneralNotifyTicket\Api\Data;

interface GeneralNotifyTicketRecordSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \Branch8\GeneralNotifyTicket\Api\Data\GeneralNotifyTicketRecordInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\GeneralNotifyTicket\Api\Data\GeneralNotifyTicketRecordInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
