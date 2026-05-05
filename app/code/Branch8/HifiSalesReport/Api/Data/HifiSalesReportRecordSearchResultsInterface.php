<?php

namespace Branch8\HifiSalesReport\Api\Data;

interface HifiSalesReportRecordSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \Branch8\HifiSalesReport\Api\Data\HifiSalesReportRecordInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\HifiSalesReport\Api\Data\HifiSalesReportRecordInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
