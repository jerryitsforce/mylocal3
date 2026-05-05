<?php

namespace Branch8\HotaiPoint\Api;

use Branch8\HotaiPoint\Api\Data\UsingCouponHistoryInterface as ModelInterface;
use Branch8\HotaiPoint\Api\Data\UsingCouponHistorySearchResultsInterface as SearchResultsInterface;

interface UsingCouponHistoryRepositoryInterface
{
    /**
     * @api
     * @param ModelInterface $record
     * @return void
     */
    public function save(ModelInterface $record);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );
}
