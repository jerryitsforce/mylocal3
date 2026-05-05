<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */
namespace Branch8\MarketPlaceProductDiscussion\Api;

use Branch8\MarketPlaceProductDiscussion\Api\Data\VoteInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface VoteRepositoryInterface
{
    /**
     * @param VoteInterface $vote
     * @return VoteInterface
     */
    public function save(VoteInterface $vote);

    /**
     * @param int $voteId
     * @return VoteInterface
     */
    public function getById(int $voteId);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param VoteInterface $vote
     * @return bool
     */
    public function delete(VoteInterface $vote);

    /**
     * @param int $voteId
     * @return bool
     */
    public function deleteById(int $voteId);
}
