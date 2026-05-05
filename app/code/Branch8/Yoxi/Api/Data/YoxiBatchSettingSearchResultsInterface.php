<?php

namespace Branch8\Yoxi\Api\Data;

interface YoxiBatchSettingSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \Branch8\Yoxi\Api\Data\YoxiBatchSettingInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\Yoxi\Api\Data\YoxiBatchSettingInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
