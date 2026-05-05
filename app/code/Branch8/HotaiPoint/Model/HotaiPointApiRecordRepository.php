<?php

namespace Branch8\HotaiPoint\Model;

use Branch8\HotaiPoint\Api\Data\HotaiPointApiRecordSearchResultsInterfaceFactory;
use Branch8\HotaiPoint\Api\HotaiPointApiRecordRepositoryInterface;
use Branch8\HotaiPoint\Model\HotaiPointApiRecord as HotaiPointApiRecordModel;
use Branch8\HotaiPoint\Model\ResourceModel\HotaiPointApiRecord;
use Branch8\HotaiPoint\Model\ResourceModel\HotaiPointApiRecord\CollectionFactory as HotaiPointApiRecordCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Branch8\HotaiPoint\Model\ResourceModel\HotaiPointApiRecord\Collection as HotaiPointApiRecordCollection;

class HotaiPointApiRecordRepository implements HotaiPointApiRecordRepositoryInterface
{
    /** @var HotaiPointApiRecord */
    protected $resource;

    /** @var HotaiPointApiRecordCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var HotaiPointApiRecordSearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    public function __construct(
        HotaiPointApiRecord $resource,
        HotaiPointApiRecordCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        HotaiPointApiRecordSearchResultsInterfaceFactory $searchResultsFactory
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
        \Branch8\HotaiPoint\Api\Data\HotaiPointApiRecordInterface $record
    ) {
        try {
            /** @var \Branch8\HotaiPoint\Model\HotaiPointApiRecord $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the hotai point api record: %1',
                $exception->getMessage()
            ));
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->collectionFactory->create();
        $collection->load();

        return $collection;
    }

    /**
     * @param string $traceNo
     * @return null|\Branch8\HotaiPoint\Model\HotaiPointApiRecord
     */
    public function getRecordByTraceNo($traceNo)
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(HotaiPointApiRecordModel::TRACE_NO, $traceNo);
        $collection->load();

        $items = $collection->getItems();

        if (count($items) == 0) {
            return null;
        }

        if (count($items) > 1) {
            throw new \Exception("Running getRecordByTraceNo got more than one records back, input traceNo: " . $traceNo);
        }

        return array_pop($items);
    }

    public function getCollectionByQuoteInfo(string $quoteId, string $quoteItemId): HotaiPointApiRecordCollection
    {
        $queryTarget = "{$quoteId}_{$quoteItemId}_";

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(HotaiPointApiRecordModel::TRANS_S_N, ["like" => "{$queryTarget}%"]);

        return $collection;
    }
}
