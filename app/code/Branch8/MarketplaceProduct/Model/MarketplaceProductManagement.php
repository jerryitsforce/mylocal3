<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Webkul\Marketplace\Model\Product as MarketplaceProduct;
use Webkul\Marketplace\Model\ProductFactory as MarketplaceProductFactory;
use Webkul\Marketplace\Model\ResourceModel\Product as MarketplaceProductResource;

class MarketplaceProductManagement
{
    /**
     * @var MarketplaceProductResource
     */
    private MarketplaceProductResource $resource;

    /**
     * @var MarketplaceProductFactory
     */
    private MarketplaceProductFactory $marketplaceProductFactory;

    /**
     * MarketplaceProductManagement constructor.
     *
     * @param MarketplaceProductResource $resource
     * @param MarketplaceProductFactory $marketplaceProductFactory
     */
    public function __construct(
        MarketplaceProductResource $resource,
        MarketplaceProductFactory  $marketplaceProductFactory
    ) {
        $this->resource = $resource;
        $this->marketplaceProductFactory = $marketplaceProductFactory;
    }

    /**
     * Create new marketplace product model.
     *
     * @return MarketplaceProduct
     */
    public function create(): MarketplaceProduct
    {
        return $this->marketplaceProductFactory->create();
    }

    /**
     * Save marketplace product.
     *
     * @param MarketplaceProduct $marketplaceProduct
     *
     * @return MarketplaceProduct
     *
     * @throws CouldNotSaveException
     */
    public function save(MarketplaceProduct $marketplaceProduct): MarketplaceProduct
    {
        try {
            $this->resource->save($marketplaceProduct);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $marketplaceProduct;
    }

    /**
     * Retrieve marketplace product by ID.
     *
     * @param int $id
     *
     * @return MarketplaceProduct
     *
     * @throws NoSuchEntityException
     */
    public function getById(int $id): MarketplaceProduct
    {
        $marketplaceProduct = $this->create();
        $this->resource->load($marketplaceProduct, $id);
        if (!$marketplaceProduct->getId()) {
            throw new NoSuchEntityException(__('Marketplace product with ID "%1" does not exist.', $id));
        }
        return $marketplaceProduct;
    }

    /**
     * Retrieve marketplace product by code.
     *
     * @param string $code
     * @param string $value
     *
     * @return MarketplaceProduct
     *
     * @throws NoSuchEntityException
     */
    public function getByCode(string $code, mixed $value): MarketplaceProduct
    {
        $marketplaceProduct = $this->create();
        $this->resource->load($marketplaceProduct, $value, $code);
        if (!$marketplaceProduct->getId()) {
            throw new NoSuchEntityException(__('Marketplace product with %1 "%2" does not exist.', $code, $value));
        }

        return $marketplaceProduct;
    }

    /**
     * Delete marketplace product.
     *
     * @param int $id
     *
     * @return bool
     *
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    /**
     * Delete marketplace product by ID.
     *
     * @param MarketplaceProduct $marketplaceProduct
     *
     * @return bool
     *
     * @throws CouldNotDeleteException
     */
    public function delete(MarketplaceProduct $marketplaceProduct): bool
    {
        try {
            $this->resource->delete($marketplaceProduct);
        } catch (\Exception $e) {
            if ($marketplaceProduct->getId()) {
                throw new CouldNotDeleteException(__(
                    'Unable to remove marketplace product with ID %1. Error: %2',
                    [$marketplaceProduct->getId(), $e->getMessage()]
                ));
            }
            throw new CouldNotDeleteException(__('Unable to remove marketplace product. Error: %1', $e->getMessage()));
        }

        return true;
    }
}
