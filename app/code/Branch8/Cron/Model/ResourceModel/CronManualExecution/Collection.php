<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Cron\Model\ResourceModel\CronManualExecution;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'cron_manual_execution_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\Cron\Model\CronManualExecution::class,
            \Branch8\Cron\Model\ResourceModel\CronManualExecution::class
        );
    }
}

