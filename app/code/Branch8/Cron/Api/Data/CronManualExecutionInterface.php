<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Cron\Api\Data;

interface CronManualExecutionInterface
{

    const JOB_CODE = 'job_code';
    const CRON_MANUAL_EXECUTION_ID = 'cron_manual_execution_id';

    /**
     * Get cron_manual_execution_id
     * @return string|null
     */
    public function getCronManualExecutionId();

    /**
     * Set cron_manual_execution_id
     * @param string $cronManualExecutionId
     * @return \Branch8\Cron\CronManualExecution\Api\Data\CronManualExecutionInterface
     */
    public function setCronManualExecutionId($cronManualExecutionId);

    /**
     * Get job
     * @return string|null
     */
    public function getJobCode();

    /**
     * Set job
     * @param string $job
     * @return \Branch8\Cron\CronManualExecution\Api\Data\CronManualExecutionInterface
     */
    public function setJobCode($job);
}

