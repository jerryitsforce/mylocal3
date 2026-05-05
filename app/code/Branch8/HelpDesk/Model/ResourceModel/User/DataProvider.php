<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\ResourceModel\User;

use Magento\Framework\Api\Search\SearchResultInterface;

/**
 * Search User Grid Data Provider
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * Return modified SearchResult
     * @param SearchResultInterface $searchResult
     * @return array
     */
    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $arrItems['totalRecords'] = $searchResult->getTotalCount();
        $arrItems['items'] = [];
        foreach ($searchResult->getItems() as $item) {
            $arrItems['items'][] = [
                'user_id' => $item->getId(),
                'name' => $item->getName(),
                'email' => $item->getEmail(),
                'username' => $item->getUserName()
            ];
        }
        return $arrItems;
    }
}
