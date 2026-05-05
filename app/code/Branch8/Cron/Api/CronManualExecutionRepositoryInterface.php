<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Cron\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface CronManualExecutionRepositoryInterface
{

    /**
     * Save cron_manual_execution
     * @param \Branch8\Cron\Api\Data\CronManualExecutionInterface $cronManualExecution
     * @return \Branch8\Cron\Api\Data\CronManualExecutionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Branch8\Cron\Api\Data\CronManualExecutionInterface $cronManualExecution
    );

    /**
     * Retrieve cron_manual_execution
     * @param string $cronManualExecutionId
     * @return \Branch8\Cron\Api\Data\CronManualExecutionInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($cronManualExecutionId);

    /**
     * Retrieve cron_manual_execution matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\Cron\Api\Data\CronManualExecutionSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete cron_manual_execution
     * @param \Branch8\Cron\Api\Data\CronManualExecutionInterface $cronManualExecution
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Branch8\Cron\Api\Data\CronManualExecutionInterface $cronManualExecution
    );

    /**
     * Delete cron_manual_execution by ID
     * @param string $cronManualExecutionId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($cronManualExecutionId);
}

