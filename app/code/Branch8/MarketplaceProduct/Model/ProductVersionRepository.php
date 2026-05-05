<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model;

use Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface;
use Branch8\MarketplaceProduct\Api\Data\ProductVersionInterfaceFactory;
use Branch8\MarketplaceProduct\Api\Data\ProductVersionSearchResultsInterface;
use Branch8\MarketplaceProduct\Api\Data\ProductVersionSearchResultsInterfaceFactory;
use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion as ProductVersionResource;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\CollectionFactory as ProductVersionCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class provides implementation of ProductVersionRepositoryInterface
 */
class ProductVersionRepository implements ProductVersionRepositoryInterface
{
    /**
     * @var array
     */
    private array $registry = [];

    /**
     * @var CollectionProcessorInterface
     */
    private CollectionProcessorInterface $collectionProcessor;

    /**
     * @var ProductVersionSearchResultsInterfaceFactory
     */
    private ProductVersionSearchResultsInterfaceFactory $searchResultsFactory;

    /**
     * @var ProductVersionCollectionFactory
     */
    private ProductVersionCollectionFactory $productVersionCollectionFactory;

    /**
     * @var ProductVersionInterfaceFactory
     */
    private ProductVersionInterfaceFactory $productVersionFactory;

    /**
     * @var ProductVersionResource
     */
    private ProductVersionResource $resource;

    /**
     * ProductVersionRepository constructor.
     *
     * @param ProductVersionResource $resource
     * @param ProductVersionInterfaceFactory $ProductVersionFactory
     * @param ProductVersionCollectionFactory $ProductVersionCollectionFactory
     * @param ProductVersionSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ProductVersionResource                      $resource,
        ProductVersionInterfaceFactory              $ProductVersionFactory,
        ProductVersionCollectionFactory             $ProductVersionCollectionFactory,
        ProductVersionSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface                $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->productVersionFactory = $ProductVersionFactory;
        $this->productVersionCollectionFactory = $ProductVersionCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritdoc
     */
    public function save(ProductVersionInterface $productVersion): ProductVersionInterface
    {
        try {
            $this->resource->save($productVersion);
            $id = $productVersion->getId();
            $this->registry[$id] = $productVersion;
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $productVersion;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $id): ProductVersionInterface
    {
        if (!isset($this->registry[$id])) {
            $productVersion = $this->productVersionFactory->create();
            $this->resource->load($productVersion, $id);
            if (!$productVersion->getId()) {
                throw new NoSuchEntityException(__('Product version with ID "%1" does not exist.', $id));
            }
            $this->registry[$id] = $productVersion;
        }
        return $this->registry[$id];
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ProductVersionSearchResultsInterface
    {
        $collection = $this->productVersionCollectionFactory->create();

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
    public function delete(ProductVersionInterface $productVersion): bool
    {
        try {
            $this->resource->delete($productVersion);
            unset($this->registry[$productVersion->getId()]);
        } catch (\Exception $e) {
            if ($productVersion->getId()) {
                throw new CouldNotDeleteException(__(
                    'Unable to remove product version with ID %1. Error: %2',
                    [$productVersion->getId(), $e->getMessage()]
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
