<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model;

use Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface;
use Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterfaceFactory;
use Branch8\MarketplaceProduct\Api\Data\ProductTempDataSearchResultsInterface;
use Branch8\MarketplaceProduct\Api\Data\ProductTempDataSearchResultsInterfaceFactory;
use Branch8\MarketplaceProduct\Api\ProductTempDataRepositoryInterface;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductTempData as ProductTempDataResource;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductTempData\CollectionFactory as ProductTempDataCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class provides implementation of ProductTempDataRepositoryInterface
 */
class ProductTempDataRepository implements ProductTempDataRepositoryInterface
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
     * @var ProductTempDataInterfaceFactory
     */
    private ProductTempDataInterfaceFactory $productTempDataFactory;

    /**
     * @var ProductTempDataResource
     */
    private ProductTempDataResource $resource;

    /**
     * @var ProductTempDataCollectionFactory
     */
    private ProductTempDataCollectionFactory $productTempDataCollectionFactory;

    /**
     * @var ProductTempDataSearchResultsInterfaceFactory
     */
    private ProductTempDataSearchResultsInterfaceFactory $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private CollectionProcessorInterface $collectionProcessor;

    /**
     * ProductTempDataRepository constructor.
     *
     * @param ProductTempDataResource $resource
     * @param ProductTempDataInterfaceFactory $productTempDataFactory
     */
    public function __construct(
        ProductTempDataResource $resource,
        ProductTempDataInterfaceFactory $productTempDataFactory,
        ProductTempDataCollectionFactory $productTempDataCollectionFactory,
        ProductTempDataSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->productTempDataFactory = $productTempDataFactory;
        $this->productTempDataCollectionFactory = $productTempDataCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritdoc
     */
    public function save(ProductTempDataInterface $productTempData): ProductTempDataInterface
    {
        try {
            $this->resource->save($productTempData);
            $id = $productTempData->getId();
            $this->registry[$id] = $productTempData;
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $productTempData;
    }

    /**
     * @inheritdoc
     */
    public function get(int $productId): ProductTempDataInterface
    {
        if (!isset($this->registryParent[$productId])) {
            $productTempData = $this->productTempDataFactory->create();
            $this->resource->load($productTempData, $productId, 'product_id');
            if (!$productTempData->getId()) {
                throw new NoSuchEntityException(__('Product data with Product ID "%1" does not exist.', $productId));
            }
            $this->registryParent[$productId] = $productTempData;
        }
        return $this->registryParent[$productId];
    }

    /**
     * @inheritdoc
     */
    public function getById(int $id): ProductTempDataInterface
    {
        if (!isset($this->registry[$id])) {
            $productVersion = $this->productTempDataFactory->create();
            $this->resource->load($productVersion, $id);
            if (!$productVersion->getId()) {
                throw new NoSuchEntityException(__('Product data with ID "%1" does not exist.', $id));
            }
            $this->registry[$id] = $productVersion;
        }
        return $this->registry[$id];
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ProductTempDataSearchResultsInterface
    {
        $collection = $this->productTempDataCollectionFactory->create();

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
    public function delete(ProductTempDataInterface $productTempData): bool
    {
        try {
            $this->resource->delete($productTempData);
            unset($this->registry[$productTempData->getId()]);
        } catch (\Exception $e) {
            if ($productTempData->getId()) {
                throw new CouldNotDeleteException(__(
                    'Unable to remove product temp with ID %1. Error: %2',
                    [$productTempData->getId(), $e->getMessage()]
                ));
            }
            throw new CouldNotDeleteException(__('Unable to remove product temp. Error: %1', $e->getMessage()));
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
