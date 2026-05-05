<?php
/**
 * Copyright © jane@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SplitOrder\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface OrderRelationRepositoryInterface
{

    /**
     * Save order_relation
     * @param \Branch8\SplitOrder\Api\Data\OrderRelationInterface $orderRelation
     * @return \Branch8\SplitOrder\Api\Data\OrderRelationInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Branch8\SplitOrder\Api\Data\OrderRelationInterface $orderRelation
    );

    /**
     * Retrieve order_relation
     * @param string $orderRelationId
     * @return \Branch8\SplitOrder\Api\Data\OrderRelationInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($orderRelationId);

    /**
     * Retrieve order_relation matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\SplitOrder\Api\Data\OrderRelationSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete order_relation
     * @param \Branch8\SplitOrder\Api\Data\OrderRelationInterface $orderRelation
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Branch8\SplitOrder\Api\Data\OrderRelationInterface $orderRelation
    );

    /**
     * Delete order_relation by ID
     * @param string $orderRelationId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($orderRelationId);
}

