<?php

declare(strict_types=1);

namespace Branch8\MarketplaceStaging\Model;

use Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface;
use Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterfaceFactory;
use Branch8\MarketplaceStaging\Api\Data\ProductVersionDataSearchResultsInterface;
use Branch8\MarketplaceStaging\Api\Data\ProductVersionDataSearchResultsInterfaceFactory;
use Branch8\MarketplaceStaging\Api\ProductVersionDataRepositoryInterface;
use Branch8\MarketplaceStaging\Model\ResourceModel\ProductVersionData as ProductVersionDataResource;
use Branch8\MarketplaceStaging\Model\ResourceModel\ProductVersionData\CollectionFactory as ProductVersionDataCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class provides implementation of ProductVersionDataRepositoryInterface
 */
class ProductVersionDataRepository implements ProductVersionDataRepositoryInterface
{
    /**
     * @var array
     */
    private array $registry = [];

    /**
     * @var array
     */
    private array $registryParent = [];

    /**
     * @var CollectionProcessorInterface
     */
    private CollectionProcessorInterface $collectionProcessor;

    /**
     * @var ProductVersionDataSearchResultsInterfaceFactory
     */
    private ProductVersionDataSearchResultsInterfaceFactory $searchResultsFactory;

    /**
     * @var ProductVersionDataCollectionFactory
     */
    private ProductVersionDataCollectionFactory $productVersionDataCollectionFactory;

    /**
     * @var ProductVersionDataInterfaceFactory
     */
    private ProductVersionDataInterfaceFactory $productVersionDataFactory;

    /**
     * @var ProductVersionDataResource
     */
    private ProductVersionDataResource $resource;

    /**
     * ProductVersionDataRepository constructor.
     *
     * @param ProductVersionDataResource $resource
     * @param ProductVersionDataInterfaceFactory $productVersionDataFactory
     * @param ProductVersionDataCollectionFactory $productVersionDataCollectionFactory
     * @param ProductVersionDataSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ProductVersionDataResource $resource,
        ProductVersionDataInterfaceFactory $productVersionDataFactory,
        ProductVersionDataCollectionFactory $productVersionDataCollectionFactory,
        ProductVersionDataSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->productVersionDataFactory = $productVersionDataFactory;
        $this->productVersionDataCollectionFactory = $productVersionDataCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritdoc
     */
    public function save(ProductVersionDataInterface $productVersionData): ProductVersionDataInterface
    {
        try {
            $this->resource->save($productVersionData);
            $id = $productVersionData->getId();
            $this->registry[$id] = $productVersionData;
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $productVersionData;
    }

    /**
     * @inheritdoc
     */
    public function get(int $parentId, bool $reload = false): ProductVersionDataInterface
    {
        if (!isset($this->registryParent[$parentId]) || $reload) {
            $productVersionData = $this->productVersionDataFactory->create();
            $this->resource->load($productVersionData, $parentId, 'parent_id');
            if (!$productVersionData->getId()) {
                throw new NoSuchEntityException(__('Product version with ID "%1" does not exist.', $parentId));
            }
            $this->registryParent[$parentId] = $productVersionData;
        }
        return $this->registryParent[$parentId];
    }

    /**
     * @inheritdoc
     */
    public function getById(int $id): ProductVersionDataInterface
    {
        if (!isset($this->registry[$id])) {
            $productVersionData = $this->productVersionDataFactory->create();
            $this->resource->load($productVersionData, $id);
            if (!$productVersionData->getId()) {
                throw new NoSuchEntityException(__('Product version with ID "%1" does not exist.', $id));
            }
            $this->registry[$id] = $productVersionData;
        }
        return $this->registry[$id];
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ProductVersionDataSearchResultsInterface
    {
        $collection = $this->productVersionDataCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(ProductVersionDataInterface $productVersionData): bool
    {
        try {
            $this->resource->delete($productVersionData);
            unset($this->registry[$productVersionData->getId()]);
        } catch (\Exception $e) {
            if ($productVersionData->getId()) {
                throw new CouldNotDeleteException(__(
                    'Unable to remove product version with ID %1. Error: %2',
                    [$productVersionData->getId(), $e->getMessage()]
                ));
            }
            throw new CouldNotDeleteException(__('Unable to remove product version. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }
}
