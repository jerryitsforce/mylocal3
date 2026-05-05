<?php

namespace Branch8\HifiSalesReport\Api;

use Branch8\HifiSalesReport\Api\Data\HifiSalesReportSubRecordInterface;

interface HifiSalesReportSubRecordRepositoryInterface
{
    /**
     * @api
     * @param HifiSalesReportSubRecordInterface $record
     * @return void
     */
    public function save(HifiSalesReportSubRecordInterface $record);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\HifiSalesReport\Api\Data\HifiSalesReportSubRecordSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );
}
