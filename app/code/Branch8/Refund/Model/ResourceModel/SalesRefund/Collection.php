<?php
/**
 * Copyright © jane@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Refund\Model\ResourceModel\SalesRefund;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'sales_refund_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\Refund\Model\SalesRefund::class,
            \Branch8\Refund\Model\ResourceModel\SalesRefund::class
        );
    }
}

