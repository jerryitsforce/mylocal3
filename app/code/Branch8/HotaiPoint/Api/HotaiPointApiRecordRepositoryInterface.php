<?php

namespace Branch8\HotaiPoint\Api;

use Branch8\HotaiPoint\Api\Data\HotaiPointApiRecordInterface;

interface HotaiPointApiRecordRepositoryInterface
{
    /**
     * @api
     * @param HotaiPointApiRecordInterface $record
     * @return void
     */
    public function save(HotaiPointApiRecordInterface $record);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\HotaiPoint\Api\Data\HotaiPointApiRecordSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );
}
