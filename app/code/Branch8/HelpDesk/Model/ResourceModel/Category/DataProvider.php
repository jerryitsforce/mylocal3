<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\ResourceModel\Category;

use Magento\Framework\Api\Search\SearchResultInterface;

/**
 * DataProvider for Category Form
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * Return Modified Search Result
     * @param SearchResultInterface $searchResult
     * @return array
     */
    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $arrItems['totalRecords'] = $searchResult->getTotalCount();
        $arrItems['items'] = [];
        foreach ($searchResult->getItems() as $item) {
            $arrItems['items'][] = [
                'category_id' => $item->getId(),
                'title' => $item->getTitle(),
                'created_at' => $item->getCreatedAt(),
                'updated_at' => $item->getUpdatedAt(),
                'store_id' => $item->getStoreId()
            ];
        }

        return $arrItems;
    }
}
