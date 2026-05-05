<?php

namespace HotaiConnected\Qware\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface QwareTicketRecordSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get QwareTicketRecord list.
     *
     * @return \HotaiConnected\Qware\Api\Data\QwareTicketRecordInterface[]
     */
    public function getItems();

    /**
     * Set QwareTicketRecord list.
     *
     * @param \HotaiConnected\Qware\Api\Data\QwareTicketRecordInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}