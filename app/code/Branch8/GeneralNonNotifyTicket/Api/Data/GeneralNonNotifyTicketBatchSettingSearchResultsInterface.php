<?php

namespace Branch8\GeneralNonNotifyTicket\Api\Data;

interface GeneralNonNotifyTicketBatchSettingSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \Branch8\GeneralNonNotifyTicket\Api\Data\GeneralNonNotifyTicketBatchSettingInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\GeneralNonNotifyTicket\Api\Data\GeneralNonNotifyTicketBatchSettingInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
