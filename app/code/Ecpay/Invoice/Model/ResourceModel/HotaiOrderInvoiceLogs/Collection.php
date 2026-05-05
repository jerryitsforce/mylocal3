<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'hotai_order_invoice_logs_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Ecpay\Invoice\Model\HotaiOrderInvoiceLogs::class,
            \Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs::class
        );
    }
}

