<?php

declare(strict_types=1);

namespace Branch8\MarketplaceStaging\Api;

interface ProductVersionDataRepositoryInterface
{
    /**
     * Save product version.
     *
     * @param \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface $productVersionData
     *
     * @return \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(
        \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface $productVersionData
    ): \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface;

    /**
     * Retrieve product version by ID.
     *
     * @param int $parentId
     *
     * @return \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $parentId, bool $reload = false): \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface;

    /**
     * Retrieve product version by ID.
     *
     * @param int $id
     *
     * @return \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface;

    /**
     * Retrieve product version matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataSearchResultsInterface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ): \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataSearchResultsInterface;

    /**
     * Delete product version.
     *
     * @param \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface $productVersionData
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(
        \Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface $productVersionData
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
