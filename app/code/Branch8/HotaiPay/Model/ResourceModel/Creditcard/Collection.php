<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HotaiPay\Model\ResourceModel\Creditcard;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'creditcard_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\HotaiPay\Model\Creditcard::class,
            \Branch8\HotaiPay\Model\ResourceModel\Creditcard::class
        );
    }
}

