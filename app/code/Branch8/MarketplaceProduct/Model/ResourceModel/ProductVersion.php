<?php
declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductVersion extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init('marketplace_product_version', 'id');
    }
}

