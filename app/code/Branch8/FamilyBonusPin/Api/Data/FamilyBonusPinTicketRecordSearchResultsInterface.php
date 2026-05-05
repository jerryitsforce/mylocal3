<?php

namespace Branch8\FamilyBonusPin\Api\Data;

interface FamilyBonusPinTicketRecordSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \Branch8\FamilyBonusPin\Api\Data\FamilyBonusPinTicketRecordInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\FamilyBonusPin\Api\Data\FamilyBonusPinTicketRecordInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
