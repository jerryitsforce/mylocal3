<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Api;

interface ProductInitialInformationRepositoryInterface
{
    /**
     * Save product initial information.
     *
     * @param \Branch8\MarketplaceProduct\Api\Data\ProductInitialInformationInterface $productInitialInfo
     *
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductInitialInformationInterface
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(
        \Branch8\MarketplaceProduct\Api\Data\ProductInitialInformationInterface $productInitialInfo
    ): \Branch8\MarketplaceProduct\Api\Data\ProductInitialInformationInterface;

    /**
     * Retrieve product initial information by product ID.
     *
     * @param int $productId
     *
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductInitialInformationInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $productId): \Branch8\MarketplaceProduct\Api\Data\ProductInitialInformationInterface;

    /**
     * Delete product initial information.
     *
     * @param \Branch8\MarketplaceProduct\Api\Data\ProductInitialInformationInterface $productInitialInfo
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(
        \Branch8\MarketplaceProduct\Api\Data\ProductInitialInformationInterface $productInitialInfo
    ): bool;
}
