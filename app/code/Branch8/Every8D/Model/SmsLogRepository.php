<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Every8D\Model;

use Branch8\Every8D\Api\Data\SmsLogInterface;
use Branch8\Every8D\Api\Data\SmsLogInterfaceFactory;
use Branch8\Every8D\Api\Data\SmsLogSearchResultsInterfaceFactory;
use Branch8\Every8D\Api\SmsLogRepositoryInterface;
use Branch8\Every8D\Model\ResourceModel\SmsLog as ResourceSmsLog;
use Branch8\Every8D\Model\ResourceModel\SmsLog\CollectionFactory as SmsLogCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class SmsLogRepository implements SmsLogRepositoryInterface
{

    /**
     * @var SmsLogCollectionFactory
     */
    protected $smsLogCollectionFactory;

    /**
     * @var SmsLog
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var SmsLogInterfaceFactory
     */
    protected $smsLogFactory;

    /**
     * @var ResourceSmsLog
     */
    protected $resource;


    /**
     * @param ResourceSmsLog $resource
     * @param SmsLogInterfaceFactory $smsLogFactory
     * @param SmsLogCollectionFactory $smsLogCollectionFactory
     * @param SmsLogSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceSmsLog $resource,
        SmsLogInterfaceFactory $smsLogFactory,
        SmsLogCollectionFactory $smsLogCollectionFactory,
        SmsLogSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->smsLogFactory = $smsLogFactory;
        $this->smsLogCollectionFactory = $smsLogCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(SmsLogInterface $smsLog)
    {
        try {
            $this->resource->save($smsLog);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the smsLog: %1',
                $exception->getMessage()
            ));
        }
        return $smsLog;
    }

    /**
     * @inheritDoc
     */
    public function get($smsLogId)
    {
        $smsLog = $this->smsLogFactory->create();
        $this->resource->load($smsLog, $smsLogId);
        if (!$smsLog->getId()) {
            throw new NoSuchEntityException(__('SmsLog with id "%1" does not exist.', $smsLogId));
        }
        return $smsLog;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->smsLogCollectionFactory->create();
        
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
    public function delete(SmsLogInterface $smsLog)
    {
        try {
            $smsLogModel = $this->smsLogFactory->create();
            $this->resource->load($smsLogModel, $smsLog->getSmslogId());
            $this->resource->delete($smsLogModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the SmsLog: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($smsLogId)
    {
        return $this->delete($this->get($smsLogId));
    }
}

