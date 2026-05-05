<?php

declare(strict_types=1);

namespace Branch8\Report\Model\ResourceModel;

use Branch8\Report\Api\Data\ProductChangeLogInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductChangeLog extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init('branch8_product_change_log', ProductChangeLogInterface::LOG_ID);
    }
}
