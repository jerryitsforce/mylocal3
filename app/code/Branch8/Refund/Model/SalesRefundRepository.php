<?php
/**
 * Copyright © jane@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Refund\Model;

use Branch8\Refund\Api\Data\SalesRefundInterface;
use Branch8\Refund\Api\Data\SalesRefundInterfaceFactory;
use Branch8\Refund\Api\Data\SalesRefundSearchResultsInterfaceFactory;
use Branch8\Refund\Api\SalesRefundRepositoryInterface;
use Branch8\Refund\Helper\ConfigurableRefundLogger;
use Branch8\Refund\Model\ResourceModel\SalesRefund as ResourceSalesRefund;
use Branch8\Refund\Model\ResourceModel\SalesRefund\CollectionFactory as SalesRefundCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class SalesRefundRepository implements SalesRefundRepositoryInterface
{
    private const LOG_CLASS_KEY = 'SalesRefundRepository';

    /**
     * @var ResourceSalesRefund
     */
    protected $resource;

    /**
     * @var SalesRefundInterfaceFactory
     */
    protected $salesRefundFactory;

    /**
     * @var SalesRefundCollectionFactory
     */
    protected $salesRefundCollectionFactory;

    /**
     * @var SalesRefund
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var ConfigurableRefundLogger
     */
    private ConfigurableRefundLogger $refundLogger;

    /**
     * @param ResourceSalesRefund $resource Persistence resource
     * @param SalesRefundInterfaceFactory $salesRefundFactory Data model factory
     * @param SalesRefundCollectionFactory $salesRefundCollectionFactory Collection factory
     * @param SalesRefundSearchResultsInterfaceFactory $searchResultsFactory API result factory
     * @param CollectionProcessorInterface $collectionProcessor Search criteria processor
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     */
    public function __construct(
        ResourceSalesRefund $resource,
        SalesRefundInterfaceFactory $salesRefundFactory,
        SalesRefundCollectionFactory $salesRefundCollectionFactory,
        SalesRefundSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        ConfigurableRefundLogger $refundLogger
    ) {
        $this->resource = $resource;
        $this->salesRefundFactory = $salesRefundFactory;
        $this->salesRefundCollectionFactory = $salesRefundCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->refundLogger = $refundLogger;
    }

    /**
     * @inheritDoc
     */
    public function save(SalesRefundInterface $salesRefund)
    {
        try {
            $this->resource->save($salesRefund);
        } catch (\Exception $exception) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $exception, 'save');

            throw new CouldNotSaveException(__(
                'Could not save the salesRefund: %1',
                $exception->getMessage()
            ));
        }
        return $salesRefund;
    }

    /**
     * @inheritDoc
     */
    public function get($salesRefundId)
    {
        $salesRefund = $this->salesRefundFactory->create();
        $this->resource->load($salesRefund, $salesRefundId);
        if (!$salesRefund->getId()) {
            throw new NoSuchEntityException(__('sales_refund with id "%1" does not exist.', $salesRefundId));
        }
        return $salesRefund;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->salesRefundCollectionFactory->create();
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(SalesRefundInterface $salesRefund)
    {
        try {
            $salesRefundModel = $this->salesRefundFactory->create();
            $this->resource->load($salesRefundModel, $salesRefund->getSalesRefundId());
            $this->resource->delete($salesRefundModel);
        } catch (\Exception $exception) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $exception, 'delete');

            throw new CouldNotDeleteException(__(
                'Could not delete the sales_refund: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($salesRefundId)
    {
        return $this->delete($this->get($salesRefundId));
    }
}

