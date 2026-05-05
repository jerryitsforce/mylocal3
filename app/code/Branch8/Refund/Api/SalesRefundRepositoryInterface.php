<?php
/**
 * Copyright © jane@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Refund\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface SalesRefundRepositoryInterface
{

    /**
     * Save sales_refund
     * @param \Branch8\Refund\Api\Data\SalesRefundInterface $salesRefund
     * @return \Branch8\Refund\Api\Data\SalesRefundInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Branch8\Refund\Api\Data\SalesRefundInterface $salesRefund
    );

    /**
     * Retrieve sales_refund
     * @param string $salesRefundId
     * @return \Branch8\Refund\Api\Data\SalesRefundInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($salesRefundId);

    /**
     * Retrieve sales_refund matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\Refund\Api\Data\SalesRefundSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete sales_refund
     * @param \Branch8\Refund\Api\Data\SalesRefundInterface $salesRefund
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Branch8\Refund\Api\Data\SalesRefundInterface $salesRefund
    );

    /**
     * Delete sales_refund by ID
     * @param string $salesRefundId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($salesRefundId);
}

