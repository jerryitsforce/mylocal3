<?php

namespace Branch8\HifiSalesReport\Model;

use Branch8\HifiSalesReport\Api\Data\HifiSalesReportSubRecordSearchResultsInterfaceFactory;
use Branch8\HifiSalesReport\Api\HifiSalesReportSubRecordRepositoryInterface;
use Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportSubRecord;
use Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportSubRecord\CollectionFactory as HifiSalesReportSubRecordCollectionFactory;
use Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportSubRecord\Collection;
use Branch8\HifiSalesReport\Model\HifiSalesReportSubRecord as Model;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class HifiSalesReportSubRecordRepository implements HifiSalesReportSubRecordRepositoryInterface
{

    /** @var HifiSalesReportSubRecord */
    protected $resource;

    /** @var HifiSalesReportSubRecordCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var HifiSalesReportSubRecordSearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    public function __construct(
        HifiSalesReportSubRecord $resource,
        HifiSalesReportSubRecordCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        HifiSalesReportSubRecordSearchResultsInterfaceFactory $searchResultsFactory
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
        \Branch8\HifiSalesReport\Api\Data\HifiSalesReportSubRecordInterface $record
    ) {
        try {
            /** @var Model $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the YoxiTicketRecord: %1',
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

    public function get(int $recordId): ?Model
    {
        $collection = $this->collectionFactory
            ->create()
            ->addFieldToFilter(Model::RECORD_ID, $recordId);

        $result = $collection->getFirstItem();

        return $result->getId() ? $result : null;
    }

    public function getRecordsByParentId(int $parentId): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(Model::PARENT_ID, $parentId);
        $collection->load();

        return $collection;
    }
}
