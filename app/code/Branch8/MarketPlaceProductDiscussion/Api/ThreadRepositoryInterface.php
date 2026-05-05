<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Api;

use Branch8\MarketPlaceProductDiscussion\Api\Data\ThreadInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
/**
 *
 */
interface ThreadRepositoryInterface
{
    /**
     * @param ThreadInterface $thread
     * @return mixed
     */
    public function save(ThreadInterface $thread);

    /**
     * @param $threadId
     * @return mixed
     */
    public function getById($threadId);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param ThreadInterface $thread
     * @return mixed
     */
    public function delete(ThreadInterface $thread);

    /**
     * @param $threadId
     * @return mixed
     */
    public function deleteById($threadId);
}
