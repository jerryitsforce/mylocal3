<?php

namespace Branch8\HifiSalesReport\Api\Data;

interface HifiSalesReportSubRecordSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \Branch8\HifiSalesReport\Api\Data\HifiSalesReportSubRecordInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\HifiSalesReport\Api\Data\HifiSalesReportSubRecordInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
