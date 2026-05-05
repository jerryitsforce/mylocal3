<?php
declare(strict_types=1);

namespace Branch8\MarketplaceStaging\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductVersionData extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('marketplace_product_version_data', 'id');
    }
}

