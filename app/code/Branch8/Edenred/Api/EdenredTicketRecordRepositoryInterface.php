<?php

namespace Branch8\Edenred\Api;

use Branch8\Edenred\Api\Data\EdenredTicketRecordInterface;

interface EdenredTicketRecordRepositoryInterface
{
    /**
     * @api
     * @param EdenredTicketRecordInterface $record
     * @return void
     */
    public function save(EdenredTicketRecordInterface $record);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\Edenred\Api\Data\EdenredTicketRecordSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );
}
