<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HotaiPay\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface CreditcardRepositoryInterface
{

    /**
     * Save creditcard
     * @param \Branch8\HotaiPay\Api\Data\CreditcardInterface $creditcard
     * @return \Branch8\HotaiPay\Api\Data\CreditcardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Branch8\HotaiPay\Api\Data\CreditcardInterface $creditcard
    );

    /**
     * Retrieve creditcard
     * @param string $creditcardId
     * @return \Branch8\HotaiPay\Api\Data\CreditcardInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($creditcardId);

    /**
     * Retrieve creditcard matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\HotaiPay\Api\Data\CreditcardSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete creditcard
     * @param \Branch8\HotaiPay\Api\Data\CreditcardInterface $creditcard
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Branch8\HotaiPay\Api\Data\CreditcardInterface $creditcard
    );

    /**
     * Delete creditcard by ID
     * @param string $creditcardId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($creditcardId);
}

