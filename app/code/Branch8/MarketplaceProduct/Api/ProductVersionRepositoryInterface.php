<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Api;

interface ProductVersionRepositoryInterface
{
    /**
     * Save product version.
     *
     * @param \Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface $productVersion
     *
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(
        \Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface $productVersion
    ): \Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface;

    /**
     * Retrieve product version by ID.
     *
     * @param int $id
     *
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): \Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface;

    /**
     * Retrieve product version matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductVersionSearchResultsInterface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ): \Branch8\MarketplaceProduct\Api\Data\ProductVersionSearchResultsInterface;

    /**
     * Delete product version.
     *
     * @param \Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface $productVersion
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(
        \Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface $productVersion
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
