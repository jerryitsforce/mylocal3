<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Every8D\Model\ResourceModel\SmsLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'smslog_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\Every8D\Model\SmsLog::class,
            \Branch8\Every8D\Model\ResourceModel\SmsLog::class
        );
    }
}

