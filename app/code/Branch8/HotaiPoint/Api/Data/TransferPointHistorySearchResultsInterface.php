<?php

namespace Branch8\HotaiPoint\Api\Data;

use Branch8\HotaiPoint\Api\Data\TransferPointHistoryInterface as ModelInterface;

interface TransferPointHistorySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get SellerFlag list.
     *
     * @return ModelInterface[]
     */
    public function getItems();

    /**
     * Set SellerFlag list.
     *
     * @param ModelInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
