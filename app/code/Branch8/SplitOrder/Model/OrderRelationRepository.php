<?php
/**
 * Copyright © jane@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SplitOrder\Model;

use Branch8\SplitOrder\Api\Data\OrderRelationInterface;
use Branch8\SplitOrder\Api\Data\OrderRelationInterfaceFactory;
use Branch8\SplitOrder\Api\Data\OrderRelationSearchResultsInterfaceFactory;
use Branch8\SplitOrder\Api\OrderRelationRepositoryInterface;
use Branch8\SplitOrder\Model\ResourceModel\OrderRelation as ResourceOrderRelation;
use Branch8\SplitOrder\Model\ResourceModel\OrderRelation\CollectionFactory as OrderRelationCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class OrderRelationRepository implements OrderRelationRepositoryInterface
{

    /**
     * @var ResourceOrderRelation
     */
    protected $resource;

    /**
     * @var OrderRelationInterfaceFactory
     */
    protected $orderRelationFactory;

    /**
     * @var OrderRelationCollectionFactory
     */
    protected $orderRelationCollectionFactory;

    /**
     * @var OrderRelation
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;


    /**
     * @param ResourceOrderRelation $resource
     * @param OrderRelationInterfaceFactory $orderRelationFactory
     * @param OrderRelationCollectionFactory $orderRelationCollectionFactory
     * @param OrderRelationSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceOrderRelation $resource,
        OrderRelationInterfaceFactory $orderRelationFactory,
        OrderRelationCollectionFactory $orderRelationCollectionFactory,
        OrderRelationSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->orderRelationFactory = $orderRelationFactory;
        $this->orderRelationCollectionFactory = $orderRelationCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(OrderRelationInterface $orderRelation)
    {
        try {
            $this->resource->save($orderRelation);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the orderRelation: %1',
                $exception->getMessage()
            ));
        }
        return $orderRelation;
    }

    /**
     * @inheritDoc
     */
    public function get($orderRelationId)
    {
        $orderRelation = $this->orderRelationFactory->create();
        $this->resource->load($orderRelation, $orderRelationId);
        if (!$orderRelation->getId()) {
            throw new NoSuchEntityException(__('order_relation with id "%1" does not exist.', $orderRelationId));
        }
        return $orderRelation;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->orderRelationCollectionFactory->create();
        
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
    public function delete(OrderRelationInterface $orderRelation)
    {
        try {
            $orderRelationModel = $this->orderRelationFactory->create();
            $this->resource->load($orderRelationModel, $orderRelation->getOrderRelationId());
            $this->resource->delete($orderRelationModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the order_relation: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($orderRelationId)
    {
        return $this->delete($this->get($orderRelationId));
    }
  
    /**
     * getParentOderIdByChildOrderId
     *
     * @param  mixed $childOrderId
     * @return int | void
     */
    public function getParentOderIdByChildOrderId($childOrderId)
    {
        $collection = $this->orderRelationCollectionFactory->create()
            ->addFieldToFilter(OrderRelationInterface::CHILD_ORDER_ID, $childOrderId);

        //Expect only one record
        foreach ($collection->getItems() as $data) {
            return $data->getParentOrderId();
        }
    }

    /**
     * getChildOderIdsByParentOrderId
     *
     * @param  mixed $parentOrderId
     * @return array
     */
    public function getChildOderIdsByParentOrderId($parentOrderId)
    {
        $collection = $this->orderRelationCollectionFactory->create()
            ->addFieldToFilter(OrderRelationInterface::PARENT_ORDER_ID, $parentOrderId);

        $item = [];
        foreach ($collection->getItems() as $data) {
            $item[] = $data->getChildOrderId();
        }
        return $item;
    }
}

