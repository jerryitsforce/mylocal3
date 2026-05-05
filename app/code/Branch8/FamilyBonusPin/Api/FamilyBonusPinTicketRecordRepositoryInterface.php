<?php

namespace Branch8\FamilyBonusPin\Api;

use Branch8\FamilyBonusPin\Api\Data\FamilyBonusPinTicketRecordInterface;

interface FamilyBonusPinTicketRecordRepositoryInterface
{
    /**
     * @api
     * @param FamilyBonusPinTicketRecordInterface $record
     * @return void
     */
    public function save(FamilyBonusPinTicketRecordInterface $record);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\FamilyBonusPin\Api\Data\FamilyBonusPinTicketRecordSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );
}
