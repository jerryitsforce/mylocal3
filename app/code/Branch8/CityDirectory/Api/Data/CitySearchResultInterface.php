<?php

namespace Branch8\CityDirectory\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface CitySearchResultInterface extends SearchResultsInterface
{
    public function getItems();

    public function setItems(array $items);
}