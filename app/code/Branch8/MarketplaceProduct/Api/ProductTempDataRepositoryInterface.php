<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Api;

interface ProductTempDataRepositoryInterface
{
    /**
     * Save product version.
     *
     * @param \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface $productTempData
     *
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(
        \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface $productTempData
    ): \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface;

    /**
     * Retrieve product data by product ID.
     *
     * @param int $productId
     *
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $productId): \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface;

    /**
     * Retrieve product version by ID.
     *
     * @param int $id
     *
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface;

    /**
     * Retrieve product version matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductTempDataSearchResultsInterface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ): \Branch8\MarketplaceProduct\Api\Data\ProductTempDataSearchResultsInterface;

    /**
     * Delete product version.
     *
     * @param \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface $productTempData
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(
        \Branch8\MarketplaceProduct\Api\Data\ProductTempDataInterface $productTempData
    ): bool;

    /**
     * Delete product version by ID.
     *
     * @param int $id
     *
     * @return bool true on success
     *
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById(int $id): bool;
}
