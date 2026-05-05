<?php

namespace Branch8\HotaiPoint\Api;

use Branch8\HotaiPoint\Api\Data\TransferPointHistoryInterface as ModelInterface;
use Branch8\HotaiPoint\Api\Data\TransferPointHitorySearchResultsInterface as SearchResultsInterface;

interface TransferPointHistoryRepositoryInterface
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
