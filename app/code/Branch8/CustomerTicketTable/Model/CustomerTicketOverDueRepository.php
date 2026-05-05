<?php

namespace Branch8\CustomerTicketTable\Model;

use Branch8\CustomerTicketTable\Api\Data\CustomerTicketSearchResultsInterfaceFactory;
use Branch8\CustomerTicketTable\Api\CustomerTicketRepositoryInterface;
use Branch8\CustomerTicketTable\Helper\Logger as CustomerTicketTableLogger;
use Branch8\CustomerTicketTable\Model\CustomerTicketOverDue as CustomerTicketOverDueModel;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicketOverDue;
use Branch8\CustomerTicketTable\Model\ResourceModel\CustomerTicketOverDue\CollectionFactory as CustomerTicketCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class CustomerTicketOverDueRepository implements CustomerTicketRepositoryInterface
{
    public const CLASS_KEY = 'CustomerTicketOverDueRepository';

    /** @var CustomerTicketOverDue */
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
     * @param CustomerTicketOverDue $resource
     * @param CustomerTicketCollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CustomerTicketSearchResultsInterfaceFactory $searchResultsFactory
     * @param CustomerTicketTableLogger $logger
     */
    public function __construct(
        CustomerTicketOverDue $resource,
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
     * Save a CustomerTicketOverDue record.
     *
     * @param \Branch8\CustomerTicketTable\Api\Data\CustomerTicketInterface $record
     * @return void
     * @throws CouldNotSaveException
     */
    public function save(
        \Branch8\CustomerTicketTable\Api\Data\CustomerTicketInterface $record
    ) {
        try {
            /** @var \Branch8\CustomerTicketTable\Model\CustomerTicketOverDue $record */
            $this->resource->save($record);
        } catch (\Exception $exception) {
            $this->logger->logException(
                $exception,
                self::CLASS_KEY,
                [
                    'message' => 'Could not save CustomerTicketOverDue.',
                    'record_id' => method_exists($record, 'getId') ? (int) $record->getId() : null,
                ]
            );

            throw new CouldNotSaveException(
                __(
                    'Could not save the CustomerTicketOverDue: %1',
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
     * Get CustomerTicketOverDue by record id.
     *
     * @param int $id
     * @return CustomerTicketOverDueModel|null
     */
    public function getById(int $id): ?CustomerTicketOverDueModel
    {
        /** @var CustomerTicketOverDueModel $result */
        $result = $this->collectionFactory->create()
            ->addFieldToFilter(CustomerTicketOverDueModel::RECORD_ID, $id)
            ->getFirstItem();

        if (is_null($result->getId())) {
            return null;
        }

        return $result;
    }
}
