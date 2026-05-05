<?php

namespace Branch8\HotaiPoint\Model;

use Branch8\HotaiPoint\Api\Data\TransferPointHistorySearchResultsInterfaceFactory as SearchResultsInterfaceFactory;
use Branch8\HotaiPoint\Api\TransferPointHistoryRepositoryInterface as ModelRepositoryInterface;
use Branch8\HotaiPoint\Api\Data\TransferPointHistoryInterface as ModelInterface;
use Branch8\HotaiPoint\Model\ResourceModel\TransferPointHistory as ResourceModel;
use Branch8\HotaiPoint\Model\ResourceModel\TransferPointHistory\Collection as Collection;
use Branch8\HotaiPoint\Model\ResourceModel\TransferPointHistory\CollectionFactory as CollectionFactory;
use Branch8\HotaiPoint\Model\TransferPointHistory as Model;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class TransferPointHistoryRepository implements ModelRepositoryInterface
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
