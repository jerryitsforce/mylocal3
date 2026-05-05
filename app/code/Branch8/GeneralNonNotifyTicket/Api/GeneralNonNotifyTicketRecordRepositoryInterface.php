<?php

namespace Branch8\GeneralNonNotifyTicket\Api;

use Branch8\GeneralNonNotifyTicket\Api\Data\GeneralNonNotifyTicketRecordInterface;

interface GeneralNonNotifyTicketRecordRepositoryInterface
{
    /**
     * @api
     * @param GeneralNonNotifyTicketRecordInterface $record
     * @return void
     */
    public function save(GeneralNonNotifyTicketRecordInterface $record);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\GeneralNonNotifyTicket\Api\Data\GeneralNonNotifyTicketRecordSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );
}
