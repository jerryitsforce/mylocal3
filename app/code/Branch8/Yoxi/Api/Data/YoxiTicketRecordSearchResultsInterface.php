<?php

namespace Branch8\Yoxi\Api\Data;

interface YoxiTicketRecordSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \Branch8\Yoxi\Api\Data\YoxiTicketRecordInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\Yoxi\Api\Data\YoxiTicketRecordInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
