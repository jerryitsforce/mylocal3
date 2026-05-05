<?php

namespace Branch8\Yoxi\Api;

use Branch8\Yoxi\Api\Data\YoxiTicketRecordInterface;

interface YoxiTicketRecordRepositoryInterface
{
    /**
     * @api
     * @param YoxiTicketRecordInterface $record
     * @return void
     */
    public function save(YoxiTicketRecordInterface $record);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\Yoxi\Api\Data\YoxiTicketRecordSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );
}
