<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Rma\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface MarketplaceRmaShippingRepositoryInterface
{

    /**
     * Save MarketplaceRmaShipping
     * @param \Branch8\Rma\Api\Data\MarketplaceRmaShippingInterface $marketplaceRmaShipping
     * @return \Branch8\Rma\Api\Data\MarketplaceRmaShippingInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Branch8\Rma\Api\Data\MarketplaceRmaShippingInterface $marketplaceRmaShipping
    );

    /**
     * Retrieve MarketplaceRmaShipping
     * @param string $marketplacermashippingId
     * @return \Branch8\Rma\Api\Data\MarketplaceRmaShippingInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($marketplacermashippingId);

    /**
     * Retrieve MarketplaceRmaShipping matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\Rma\Api\Data\MarketplaceRmaShippingSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete MarketplaceRmaShipping
     * @param \Branch8\Rma\Api\Data\MarketplaceRmaShippingInterface $marketplaceRmaShipping
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Branch8\Rma\Api\Data\MarketplaceRmaShippingInterface $marketplaceRmaShipping
    );

    /**
     * Delete MarketplaceRmaShipping by ID
     * @param string $marketplacermashippingId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($marketplacermashippingId);
}

