<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Cron\Model;

use Branch8\Cron\Api\CronManualExecutionRepositoryInterface;
use Branch8\Cron\Api\Data\CronManualExecutionInterface;
use Branch8\Cron\Api\Data\CronManualExecutionInterfaceFactory;
use Branch8\Cron\Api\Data\CronManualExecutionSearchResultsInterfaceFactory;
use Branch8\Cron\Model\ResourceModel\CronManualExecution as ResourceCronManualExecution;
use Branch8\Cron\Model\ResourceModel\CronManualExecution\CollectionFactory as CronManualExecutionCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class CronManualExecutionRepository implements CronManualExecutionRepositoryInterface
{

    /**
     * @var ResourceCronManualExecution
     */
    protected $resource;

    /**
     * @var CronManualExecutionInterfaceFactory
     */
    protected $cronManualExecutionFactory;

    /**
     * @var CronManualExecutionCollectionFactory
     */
    protected $cronManualExecutionCollectionFactory;

    /**
     * @var CronManualExecution
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;


    /**
     * @param ResourceCronManualExecution $resource
     * @param CronManualExecutionInterfaceFactory $cronManualExecutionFactory
     * @param CronManualExecutionCollectionFactory $cronManualExecutionCollectionFactory
     * @param CronManualExecutionSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceCronManualExecution $resource,
        CronManualExecutionInterfaceFactory $cronManualExecutionFactory,
        CronManualExecutionCollectionFactory $cronManualExecutionCollectionFactory,
        CronManualExecutionSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->cronManualExecutionFactory = $cronManualExecutionFactory;
        $this->cronManualExecutionCollectionFactory = $cronManualExecutionCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(
        CronManualExecutionInterface $cronManualExecution
    ) {
        try {
            $this->resource->save($cronManualExecution);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the cronManualExecution: %1',
                $exception->getMessage()
            ));
        }
        return $cronManualExecution;
    }

    /**
     * @inheritDoc
     */
    public function get($cronManualExecutionId)
    {
        $cronManualExecution = $this->cronManualExecutionFactory->create();
        $this->resource->load($cronManualExecution, $cronManualExecutionId);
        if (!$cronManualExecution->getId()) {
            throw new NoSuchEntityException(__('cron_manual_execution with id "%1" does not exist.', $cronManualExecutionId));
        }
        return $cronManualExecution;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->cronManualExecutionCollectionFactory->create();
        
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
    public function delete(
        CronManualExecutionInterface $cronManualExecution
    ) {
        try {
            $cronManualExecutionModel = $this->cronManualExecutionFactory->create();
            $this->resource->load($cronManualExecutionModel, $cronManualExecution->getCronManualExecutionId());
            $this->resource->delete($cronManualExecutionModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the cron_manual_execution: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($cronManualExecutionId)
    {
        return $this->delete($this->get($cronManualExecutionId));
    }
}

