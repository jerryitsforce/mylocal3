<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Rma\Model;

use Branch8\Rma\Api\Data\MarketplaceRmaStatusHistoryInterface;
use Branch8\Rma\Api\Data\MarketplaceRmaStatusHistoryInterfaceFactory;
use Branch8\Rma\Api\Data\MarketplaceRmaStatusHistorySearchResultsInterfaceFactory;
use Branch8\Rma\Api\MarketplaceRmaStatusHistoryRepositoryInterface;
use Branch8\Rma\Model\ResourceModel\MarketplaceRmaStatusHistory as ResourceMarketplaceRmaStatusHistory;
use Branch8\Rma\Model\ResourceModel\MarketplaceRmaStatusHistory\CollectionFactory as MarketplaceRmaStatusHistoryCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class MarketplaceRmaStatusHistoryRepository implements MarketplaceRmaStatusHistoryRepositoryInterface
{
    /**
     * Log option value for this repository.
     */
    private const LOG_OPTION = 'MarketplaceRmaStatusHistoryRepository';

    /**
     * @var ResourceMarketplaceRmaStatusHistory
     */
    protected $resource;

    /**
     * @var MarketplaceRmaStatusHistoryInterfaceFactory
     */
    protected $marketplaceRmaStatusHistoryFactory;

    /**
     * @var MarketplaceRmaStatusHistoryCollectionFactory
     */
    protected $marketplaceRmaStatusHistoryCollectionFactory;

    /**
     * @var MarketplaceRmaStatusHistory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;


    /**
     * @param ResourceMarketplaceRmaStatusHistory $resource
     * @param MarketplaceRmaStatusHistoryInterfaceFactory $marketplaceRmaStatusHistoryFactory
     * @param MarketplaceRmaStatusHistoryCollectionFactory $marketplaceRmaStatusHistoryCollectionFactory
     * @param MarketplaceRmaStatusHistorySearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceMarketplaceRmaStatusHistory $resource,
        MarketplaceRmaStatusHistoryInterfaceFactory $marketplaceRmaStatusHistoryFactory,
        MarketplaceRmaStatusHistoryCollectionFactory $marketplaceRmaStatusHistoryCollectionFactory,
        MarketplaceRmaStatusHistorySearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->marketplaceRmaStatusHistoryFactory = $marketplaceRmaStatusHistoryFactory;
        $this->marketplaceRmaStatusHistoryCollectionFactory = $marketplaceRmaStatusHistoryCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(
        MarketplaceRmaStatusHistoryInterface $marketplaceRmaStatusHistory
    ) {
        try {
            $this->resource->save($marketplaceRmaStatusHistory);
        } catch (\Exception $exception) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($exception, self::LOG_OPTION, __METHOD__);
            throw new CouldNotSaveException(__(
                'Could not save the marketplaceRmaStatusHistory: %1',
                $exception->getMessage()
            ));
        }
        return $marketplaceRmaStatusHistory;
    }

    /**
     * @inheritDoc
     */
    public function get($marketplaceRmaStatusHistoryId)
    {
        $marketplaceRmaStatusHistory = $this->marketplaceRmaStatusHistoryFactory->create();
        $this->resource->load($marketplaceRmaStatusHistory, $marketplaceRmaStatusHistoryId);
        if (!$marketplaceRmaStatusHistory->getId()) {
            throw new NoSuchEntityException(__('MarketplaceRmaStatusHistory with id "%1" does not exist.', $marketplaceRmaStatusHistoryId));
        }
        return $marketplaceRmaStatusHistory;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->marketplaceRmaStatusHistoryCollectionFactory->create();
        
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
        MarketplaceRmaStatusHistoryInterface $marketplaceRmaStatusHistory
    ) {
        try {
            $marketplaceRmaStatusHistoryModel = $this->marketplaceRmaStatusHistoryFactory->create();
            $this->resource->load($marketplaceRmaStatusHistoryModel, $marketplaceRmaStatusHistory->getMarketplacermastatushistoryId());
            $this->resource->delete($marketplaceRmaStatusHistoryModel);
        } catch (\Exception $exception) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($exception, self::LOG_OPTION, __METHOD__);
            throw new CouldNotDeleteException(__(
                'Could not delete the MarketplaceRmaStatusHistory: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($marketplaceRmaStatusHistoryId)
    {
        return $this->delete($this->get($marketplaceRmaStatusHistoryId));
    }
}

