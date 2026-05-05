<?php

namespace Branch8\HifiSalesReport\Api;

use Branch8\HifiSalesReport\Api\Data\HifiSalesReportRecordInterface;

interface HifiSalesReportRecordRepositoryInterface
{
    /**
     * @api
     * @param HifiSalesReportRecordInterface $record
     * @return void
     */
    public function save(HifiSalesReportRecordInterface $record);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\HifiSalesReport\Api\Data\HifiSalesReportRecordSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );
}
