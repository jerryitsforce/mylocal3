<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model;

use Branch8\MarketPlaceProductDiscussion\Api\VoteRepositoryInterface;
use Branch8\MarketPlaceProductDiscussion\Api\Data\VoteInterface;
use Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Vote as ResourceVote;
use Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Vote\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class VoteRepository implements VoteRepositoryInterface
{
    private ResourceVote $resource;
    private VoteFactory $voteFactory;
    private CollectionFactory $collectionFactory;
    private CollectionProcessorInterface $collectionProcessor;
    private SearchResultsInterfaceFactory $searchResultsFactory;

    public function __construct(
        ResourceVote $resource,
        VoteFactory $voteFactory,
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->resource = $resource;
        $this->voteFactory = $voteFactory;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function save(VoteInterface $vote)
    {
        $this->resource->save($vote);
        return $vote;
    }

    public function getById(int $voteId)
    {
        $vote = $this->voteFactory->create();
        $this->resource->load($vote, $voteId);

        if (!$vote->getId()) {
            throw new NoSuchEntityException(__('Vote not found.'));
        }

        return $vote;
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(VoteInterface $vote)
    {
        $this->resource->delete($vote);
        return true;
    }

    public function deleteById(int $voteId)
    {
        return $this->delete($this->getById($voteId));
    }
}
