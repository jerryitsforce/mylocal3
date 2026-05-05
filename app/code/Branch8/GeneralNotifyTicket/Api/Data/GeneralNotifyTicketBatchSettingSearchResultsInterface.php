<?php

namespace Branch8\GeneralNotifyTicket\Api\Data;

interface GeneralNotifyTicketBatchSettingSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \Branch8\GeneralNotifyTicket\Api\Data\GeneralNotifyTicketBatchSettingInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\GeneralNotifyTicket\Api\Data\GeneralNotifyTicketBatchSettingInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
