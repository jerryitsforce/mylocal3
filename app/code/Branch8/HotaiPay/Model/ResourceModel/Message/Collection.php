<?php
/**
 * Copyright © dev@branch8 All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HotaiPay\Model\ResourceModel\Message;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'message_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\HotaiPay\Model\Message::class,
            \Branch8\HotaiPay\Model\ResourceModel\Message::class
        );
    }
}

