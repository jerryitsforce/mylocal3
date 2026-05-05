<?php

namespace Branch8\CustomerTicketTable\Model;

use Branch8\CustomerTicketTable\Api\Data\CustomerTicketSearchResultsInterfaceFactory;
use Branch8\CustomerTicketTable\Api\CustomerTicketRepositoryInterface;
use Branch8\CustomerTicketTable\Helper\Logger as CustomerTicketTableLogger;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\Collection as Collection;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicket\CollectionFactory as CustomerTicketCollectionFactory;
use Branch8\CustomerTicketTable\Model\CustomerTicket as CustomerTicketModel;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class CustomerTicketRepository implements CustomerTicketRepositoryInterface
{
    public const CLASS_KEY = 'CustomerTicketRepository';

    /** @var CustomerTicket */
    protected $resource;

    /** @var CustomerTicketCollectionFactory */
    protected $collectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var CustomerTicketSearchResultsInterfaceFactory */
    protected $searchResultsFactory;

    /** @var CustomerTicketTableLogger */
    protected CustomerTicketTableLogger $logger;

    /**
     * @param CustomerTicket $resource
     * @param CustomerTicketCollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CustomerTicketSearchResultsInterfaceFactory $searchResultsFactory
     * @param CustomerTicketTableLogger $logger
     */
    public function __construct(
        CustomerTicket $resource,
        CustomerTicketCollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        CustomerTicketSearchResultsInterfaceFactory $searchResultsFactory,
        CustomerTicketTableLogger $logger
    ) {
        $this->resource             = $resource;
        $this->collectionFactory    = $collectionFactory;
        $this->collectionProcessor  = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->logger               = $logger;
    }

    /**
     * Save a CustomerTicket record.
     *
     * @param \Branch8\CustomerTicketTable\Api\Data\CustomerTicketInterface $record
     * @return void
     * @throws CouldNotSaveException
     */
    public function save(
        \Branch8\CustomerTicketTable\Api\Data\CustomerTicketInterface $record
    ) {
        try {
            /** @var \Branch8\CustomerTicketTable\Model\CustomerTicket $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            $this->logger->logException(
                $exception,
                self::CLASS_KEY,
                [
                    'message' => 'Could not save CustomerTicket.',
                    'record_id' => method_exists($record, 'getId') ? (int) $record->getId() : null,
                ]
            );

            throw new CouldNotSaveException(
                __(
                    'Could not save the CustomerTicket: %1',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * Get list by search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Branch8\CustomerTicketTable\Api\Data\CustomerTicketSearchResultsInterface
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

    /**
     * Get CustomerTicket by record id.
     *
     * @param int $id
     * @return CustomerTicketModel|null
     */
    public function getById(int $id): ?CustomerTicketModel
    {
        /** @var CustomerTicketModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(CustomerTicketModel::RECORD_ID, $id)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    /**
     * Get CustomerTicket by type and ticket table record id.
     *
     * @param int $type
     * @param int $recordId
     * @return CustomerTicketModel|null
     */
    public function getByTypeAndTicketRecordId(int $type, int $recordId): ?CustomerTicketModel
    {
        /** @var CustomerTicketModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(CustomerTicketModel::TYPE, $type)
            ->addFieldToFilter(CustomerTicketModel::TICKET_TABLE_RECORD_ID, $recordId)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }

    /**
     * Get CustomerTicket collection by sales order item ids.
     *
     * @param int[] $orderItemIdsArray
     * @return Collection
     */
    public function getByOrderItemIdsArray(array $orderItemIdsArray): Collection
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(CustomerTicketModel::SALES_ORDER_ITEM_ID, ['in' => $orderItemIdsArray]);

        return $collection;
    }

    /**
     * Get CustomerTicket by sales order item id.
     *
     * @param int $orderItemId
     * @return CustomerTicketModel|null
     */
    public function getTicketByOrderItemId(int $orderItemId): ?CustomerTicketModel
    {
        /** @var CustomerTicketModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(CustomerTicketModel::SALES_ORDER_ITEM_ID, $orderItemId)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }
}
