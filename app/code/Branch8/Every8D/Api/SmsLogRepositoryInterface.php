<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Every8D\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface SmsLogRepositoryInterface
{

    /**
     * Save SmsLog
     * @param \Branch8\Every8D\Api\Data\SmsLogInterface $smsLog
     * @return \Branch8\Every8D\Api\Data\SmsLogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Branch8\Every8D\Api\Data\SmsLogInterface $smsLog
    );

    /**
     * Retrieve SmsLog
     * @param string $smslogId
     * @return \Branch8\Every8D\Api\Data\SmsLogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($smslogId);

    /**
     * Retrieve SmsLog matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\Every8D\Api\Data\SmsLogSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete SmsLog
     * @param \Branch8\Every8D\Api\Data\SmsLogInterface $smsLog
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Branch8\Every8D\Api\Data\SmsLogInterface $smsLog
    );

    /**
     * Delete SmsLog by ID
     * @param string $smslogId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($smslogId);
}

