<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Rma\Model;

use Branch8\Rma\Api\Data\MarketplaceRmaShippingInterface;
use Branch8\Rma\Api\Data\MarketplaceRmaShippingInterfaceFactory;
use Branch8\Rma\Api\Data\MarketplaceRmaShippingSearchResultsInterfaceFactory;
use Branch8\Rma\Api\MarketplaceRmaShippingRepositoryInterface;
use Branch8\Rma\Model\ResourceModel\MarketplaceRmaShipping as ResourceMarketplaceRmaShipping;
use Branch8\Rma\Model\ResourceModel\MarketplaceRmaShipping\CollectionFactory as MarketplaceRmaShippingCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class MarketplaceRmaShippingRepository implements MarketplaceRmaShippingRepositoryInterface
{
    /**
     * Log option value for this repository.
     */
    private const LOG_OPTION = 'MarketplaceRmaShippingRepository';

    /**
     * @var ResourceMarketplaceRmaShipping
     */
    protected $resource;

    /**
     * @var MarketplaceRmaShippingInterfaceFactory
     */
    protected $marketplaceRmaShippingFactory;

    /**
     * @var MarketplaceRmaShippingCollectionFactory
     */
    protected $marketplaceRmaShippingCollectionFactory;

    /**
     * @var MarketplaceRmaShipping
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;


    /**
     * @param ResourceMarketplaceRmaShipping $resource
     * @param MarketplaceRmaShippingInterfaceFactory $marketplaceRmaShippingFactory
     * @param MarketplaceRmaShippingCollectionFactory $marketplaceRmaShippingCollectionFactory
     * @param MarketplaceRmaShippingSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceMarketplaceRmaShipping $resource,
        MarketplaceRmaShippingInterfaceFactory $marketplaceRmaShippingFactory,
        MarketplaceRmaShippingCollectionFactory $marketplaceRmaShippingCollectionFactory,
        MarketplaceRmaShippingSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->marketplaceRmaShippingFactory = $marketplaceRmaShippingFactory;
        $this->marketplaceRmaShippingCollectionFactory = $marketplaceRmaShippingCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(
        MarketplaceRmaShippingInterface $marketplaceRmaShipping
    ) {
        try {
            $this->resource->save($marketplaceRmaShipping);
        } catch (\Exception $exception) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($exception, self::LOG_OPTION, __METHOD__);
            throw new CouldNotSaveException(__(
                'Could not save the marketplaceRmaShipping: %1',
                $exception->getMessage()
            ));
        }
        return $marketplaceRmaShipping;
    }

    /**
     * @inheritDoc
     */
    public function get($marketplaceRmaShippingId)
    {
        $marketplaceRmaShipping = $this->marketplaceRmaShippingFactory->create();
        $this->resource->load($marketplaceRmaShipping, $marketplaceRmaShippingId);
        if (!$marketplaceRmaShipping->getId()) {
            throw new NoSuchEntityException(__('MarketplaceRmaShipping with id "%1" does not exist.', $marketplaceRmaShippingId));
        }
        return $marketplaceRmaShipping;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->marketplaceRmaShippingCollectionFactory->create();
        
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
        MarketplaceRmaShippingInterface $marketplaceRmaShipping
    ) {
        try {
            $marketplaceRmaShippingModel = $this->marketplaceRmaShippingFactory->create();
            $this->resource->load($marketplaceRmaShippingModel, $marketplaceRmaShipping->getMarketplacermashippingId());
            $this->resource->delete($marketplaceRmaShippingModel);
        } catch (\Exception $exception) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($exception, self::LOG_OPTION, __METHOD__);
            throw new CouldNotDeleteException(__(
                'Could not delete the MarketplaceRmaShipping: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($marketplaceRmaShippingId)
    {
        return $this->delete($this->get($marketplaceRmaShippingId));
    }
}

