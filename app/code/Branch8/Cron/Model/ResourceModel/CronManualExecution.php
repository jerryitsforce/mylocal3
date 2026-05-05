<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Cron\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CronManualExecution extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('cron_manual_execution', 'cron_manual_execution_id');
    }
}

