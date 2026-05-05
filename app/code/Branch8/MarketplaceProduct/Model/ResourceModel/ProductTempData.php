<?php
declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductTempData extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected $_isPkAutoIncrement = false; // @codingStandardsIgnoreLine - required by parent class

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('marketplace_product_temp', 'temp_id');
    }
}

