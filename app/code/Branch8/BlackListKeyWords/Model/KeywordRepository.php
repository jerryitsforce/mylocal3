<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       20/03/2026
 */

namespace Branch8\BlackListKeyWords\Model;

use Branch8\BlackListKeyWords\Api\Data\KeywordInterface;
use Branch8\BlackListKeyWords\Api\KeywordRepositoryInterface;
use Branch8\BlackListKeyWords\Model\ResourceModel\Keyword as ResourceThread;
use Branch8\BlackListKeyWords\Model\ResourceModel\Keyword\CollectionFactory;
use Branch8\BlackListKeyWords\Model\KeywordFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class KeywordRepository implements KeywordRepositoryInterface
{
    private $resource;
    private $keywordFactory;
    private $collectionFactory;
    private $collectionProcessor;

    /**
     * @param ResourceThread $resource
     * @param \Branch8\BlackListKeyWords\Model\KeywordFactory $threadFactory
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceThread $resource,
        KeywordFactory $threadFactory,
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->keywordFactory = $threadFactory;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @param KeywordInterface $keyword
     * @return KeywordInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(KeywordInterface $keyword)
    {
        $this->resource->save($keyword);
        return $keyword;
    }

    /**
     * @param $id
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $thread = $this->keywordFactory->create();
        $this->resource->load($thread, $id);

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
     * @param KeywordInterface $keyword
     * @return true
     * @throws \Exception
     */
    public function delete(KeywordInterface $keyword)
    {
        $this->resource->delete($keyword);
        return true;
    }

    /**
     * @param $id
     * @return true
     * @throws NoSuchEntityException
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }
}
