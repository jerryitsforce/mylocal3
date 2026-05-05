<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Rma\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface MarketplaceRmaStatusHistoryRepositoryInterface
{

    /**
     * Save MarketplaceRmaStatusHistory
     * @param \Branch8\Rma\Api\Data\MarketplaceRmaStatusHistoryInterface $marketplaceRmaStatusHistory
     * @return \Branch8\Rma\Api\Data\MarketplaceRmaStatusHistoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Branch8\Rma\Api\Data\MarketplaceRmaStatusHistoryInterface $marketplaceRmaStatusHistory
    );

    /**
     * Retrieve MarketplaceRmaStatusHistory
     * @param string $marketplacermastatushistoryId
     * @return \Branch8\Rma\Api\Data\MarketplaceRmaStatusHistoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($marketplacermastatushistoryId);

    /**
     * Retrieve MarketplaceRmaStatusHistory matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\Rma\Api\Data\MarketplaceRmaStatusHistorySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete MarketplaceRmaStatusHistory
     * @param \Branch8\Rma\Api\Data\MarketplaceRmaStatusHistoryInterface $marketplaceRmaStatusHistory
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Branch8\Rma\Api\Data\MarketplaceRmaStatusHistoryInterface $marketplaceRmaStatusHistory
    );

    /**
     * Delete MarketplaceRmaStatusHistory by ID
     * @param string $entityId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($entityId);
}

