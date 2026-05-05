<?php

namespace Branch8\HotaiPoint\Model;

use Branch8\HotaiPoint\Api\Data\UsingCouponHistorySearchResultsInterfaceFactory as SearchResultsInterfaceFactory;
use Branch8\HotaiPoint\Api\UsingCouponHistoryRepositoryInterface as ModelRepositoryInterface;
use Branch8\HotaiPoint\Api\Data\UsingCouponHistoryInterface as ModelInterface;
use Branch8\HotaiPoint\Model\ResourceModel\UsingCouponHistory as ResourceModel;
use Branch8\HotaiPoint\Model\ResourceModel\UsingCouponHistory\Collection as Collection;
use Branch8\HotaiPoint\Model\ResourceModel\UsingCouponHistory\CollectionFactory as CollectionFactory;
use Branch8\HotaiPoint\Model\UsingCouponHistory as Model;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class UsingCouponHistoryRepository implements ModelRepositoryInterface
{
    /** @var ResourceModel */
    protected $resource;

    /** @var CollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var SearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    public function __construct(
        ResourceModel $resource,
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->resource             = $resource;
        $this->collectionFactory    = $collectionFactory;
        $this->collectionProcessor  = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(
        ModelInterface $record
    ) {
        try {
            /** @var Model $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the GeneralNonNotifyTicketRecord: %1',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($criteria);
        $searchResult->setItems($collection->getData());
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }
}
