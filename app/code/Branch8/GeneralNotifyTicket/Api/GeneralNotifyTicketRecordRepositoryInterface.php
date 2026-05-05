<?php

namespace Branch8\GeneralNotifyTicket\Api;

use Branch8\GeneralNotifyTicket\Api\Data\GeneralNotifyTicketRecordInterface;

interface GeneralNotifyTicketRecordRepositoryInterface
{
    /**
     * @api
     * @param GeneralNotifyTicketRecordInterface $record
     * @return void
     */
    public function save(GeneralNotifyTicketRecordInterface $record);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\GeneralNotifyTicket\Api\Data\GeneralNotifyTicketRecordSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );
}
