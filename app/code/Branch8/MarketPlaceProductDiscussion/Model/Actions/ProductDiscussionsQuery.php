<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       27/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model\Actions;

use Branch8\MarketPlaceProductDiscussion\Api\ProductDiscussionsQueryInterface;
use Branch8\MarketPlaceProductDiscussion\Api\ThreadRepositoryInterface;
use Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Message\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SearchCriteriaInterface;

class ProductDiscussionsQuery implements ProductDiscussionsQueryInterface
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $messageCollectionFactory;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    /**
     * @var ThreadRepositoryInterface
     */
    private ThreadRepositoryInterface $threadRepository;

    /**
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param CollectionFactory $messageCollectionFactory
     * @param ThreadRepositoryInterface $threadRepository
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        CollectionFactory            $messageCollectionFactory,
        ThreadRepositoryInterface    $threadRepository
    )
    {
        $this->threadRepository = $threadRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilderFactory->create();
        $this->messageCollectionFactory = $messageCollectionFactory;
    }

    /**
     * @param SearchCriteriaInterface|null $threadCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function get(SearchCriteriaInterface $threadCriteria = null)
    {
        $criteria = $threadCriteria ?: $this->searchCriteriaBuilder->create();
        return $this->threadRepository->getList($criteria);
    }

}
