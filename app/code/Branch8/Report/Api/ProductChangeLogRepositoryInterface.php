<?php

declare(strict_types=1);

namespace Branch8\Report\Api;

interface ProductChangeLogRepositoryInterface
{
    /**
     * Save seller action history.
     *
     * @param \Branch8\Report\Api\Data\ProductChangeLogInterface $productChangeLog
     *
     * @return \Branch8\Report\Api\Data\ProductChangeLogInterface
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(
        \Branch8\Report\Api\Data\ProductChangeLogInterface $productChangeLog
    ): \Branch8\Report\Api\Data\ProductChangeLogInterface;

    /**
     * Retrieve seller action history by ID.
     *
     * @param int $id
     *
     * @return \Branch8\Report\Api\Data\ProductChangeLogInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): \Branch8\Report\Api\Data\ProductChangeLogInterface;
}

