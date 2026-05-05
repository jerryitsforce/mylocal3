<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderHelpDesk\Api;
/**
 * This interface help to search customer order
 * Todo : Will index all parent order to Elastic to increase perfomance when order increase day by day
 */

use Branch8\MarketPlaceParentOrderHelpDesk\Api\Data\FindYourParentOrdersSearchResultInterface;

interface FindYourParentOrderServiceInterface
{
    /**
     * @return FindYourParentOrdersSearchResultInterface
     */
    public function search(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
