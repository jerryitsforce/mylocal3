<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Cron\Model;

use Branch8\Cron\Api\Data\CronManualExecutionInterface;
use Magento\Framework\Model\AbstractModel;

class CronManualExecution extends AbstractModel implements CronManualExecutionInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Branch8\Cron\Model\ResourceModel\CronManualExecution::class);
    }

    /**
     * @inheritDoc
     */
    public function getCronManualExecutionId()
    {
        return $this->getData(self::CRON_MANUAL_EXECUTION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCronManualExecutionId($cronManualExecutionId)
    {
        return $this->setData(self::CRON_MANUAL_EXECUTION_ID, $cronManualExecutionId);
    }

    /**
     * @inheritDoc
     */
    public function getJobCode()
    {
        return $this->getData(self::JOB_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setJobCode($job)
    {
        return $this->setData(self::JOB_CODE, $job);
    }
}

