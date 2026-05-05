<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       27/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface ProductDiscussionsQueryInterface
{
    /**
     * @param SearchCriteriaInterface|null $threadCriteria
     * @return mixed
     */
    public function get(
        SearchCriteriaInterface $threadCriteria = null
    );
}
