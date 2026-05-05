<?php

namespace Branch8\HifiSalesReport\Model;

use Branch8\HifiSalesReport\Api\Data\HifiSalesReportRecordSearchResultsInterfaceFactory;
use Branch8\HifiSalesReport\Api\HifiSalesReportRecordRepositoryInterface;
use Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportRecord;
use Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportRecord\Collection as HifiSalesReportRecordCollection;
use Branch8\HifiSalesReport\Model\ResourceModel\HifiSalesReportRecord\CollectionFactory as HifiSalesReportRecordCollectionFactory;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecord as HifiSalesReportRecordModel;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class HifiSalesReportRecordRepository implements HifiSalesReportRecordRepositoryInterface
{

    /** @var HifiSalesReportRecord */
    protected $resource;

    /** @var HifiSalesReportRecordCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var HifiSalesReportRecordSearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    public function __construct(
        HifiSalesReportRecord $resource,
        HifiSalesReportRecordCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        HifiSalesReportRecordSearchResultsInterfaceFactory $searchResultsFactory
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
        \Branch8\HifiSalesReport\Api\Data\HifiSalesReportRecordInterface $record
    ) {
        try {
            /** @var \Branch8\HifiSalesReport\Model\HifiSalesReportRecord $record */
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

    public function get(int $recordId): ?HifiSalesReportRecordModel
    {
        $collection = $this->collectionFactory
            ->create()
            ->addFieldToFilter(HifiSalesReportRecordModel::RECORD_ID, $recordId);

        $result = $collection->getFirstItem();

        return $result->getId() ? $result : null;
    }
}
