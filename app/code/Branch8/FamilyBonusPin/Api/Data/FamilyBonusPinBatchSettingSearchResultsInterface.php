<?php

namespace Branch8\FamilyBonusPin\Api\Data;

interface FamilyBonusPinBatchSettingSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \Branch8\FamilyBonusPin\Api\Data\FamilyBonusPinBatchSettingInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\FamilyBonusPin\Api\Data\FamilyBonusPinBatchSettingInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
