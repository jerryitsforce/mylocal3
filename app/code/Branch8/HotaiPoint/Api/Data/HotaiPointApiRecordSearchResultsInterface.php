<?php

namespace Branch8\HotaiPoint\Api\Data;

interface HotaiPointApiRecordSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get SellerFlag list.
     *
     * @return \Branch8\HotaiPointApiRecord\Api\Data\HotaiPointApiRecordInterface[]
     */
    public function getItems();

    /**
     * Set SellerFlag list.
     *
     * @param \Branch8\HotaiPointApiRecord\Api\Data\HotaiPointApiRecordInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
