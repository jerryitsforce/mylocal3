<?php
namespace Branch8\MarketPlaceProductDiscussion\Model;

use Branch8\MarketPlaceProductDiscussion\Api\ThreadRepositoryInterface;
use Branch8\MarketPlaceProductDiscussion\Api\Data\ThreadInterface;
use Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Thread as ResourceThread;
use Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Thread\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class ThreadRepository implements ThreadRepositoryInterface
{
    private $resource;
    private $threadFactory;
    private $collectionFactory;
    private $collectionProcessor;

    public function __construct(
        ResourceThread $resource,
        ThreadFactory $threadFactory,
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->threadFactory = $threadFactory;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @param ThreadInterface $thread
     * @return ThreadInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(ThreadInterface $thread)
    {
        $this->resource->save($thread);
        return $thread;
    }

    /**
     * @param $threadId
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getById($threadId)
    {
        $thread = $this->threadFactory->create();
        $this->resource->load($thread, $threadId);

        if (!$thread->getId()) {
            throw new NoSuchEntityException(__('Thread not found.'));
        }

        return $thread;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return ResourceThread\Collection
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        return $collection;
    }

    /**
     * @param ThreadInterface $thread
     * @return true
     * @throws \Exception
     */
    public function delete(ThreadInterface $thread)
    {
        $this->resource->delete($thread);
        return true;
    }

    /**
     * @param $threadId
     * @return true
     * @throws NoSuchEntityException
     */
    public function deleteById($threadId)
    {
        return $this->delete($this->getById($threadId));
    }
}
