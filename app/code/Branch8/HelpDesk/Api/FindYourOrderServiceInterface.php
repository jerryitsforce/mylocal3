<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Api;
/**
 * This interface help to search customer order
 * Todo : Will index all order to Elastic to increase perfomance when order increase day by day
 */

use Branch8\HelpDesk\Api\Data\FindYourOrdersSearchResultInterface;

interface FindYourOrderServiceInterface
{
    /**
     * @return FindYourOrdersSearchResultInterface
     */
    public function search(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
