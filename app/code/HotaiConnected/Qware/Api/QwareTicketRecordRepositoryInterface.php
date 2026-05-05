<?php

namespace HotaiConnected\Qware\Api;

use HotaiConnected\Qware\Api\Data\QwareTicketRecordInterface;

interface QwareTicketRecordRepositoryInterface
{
    /**
     * @api
     * @param QwareTicketRecordInterface $record
     * @return void
     */
    public function save(QwareTicketRecordInterface $record);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \HotaiConnected\Qware\Api\Data\QwareTicketRecordSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );
}