<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductInitialInformation extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected $_isPkAutoIncrement = false; // @codingStandardsIgnoreLine - required by parent class

    /**
     * @inheritDoc
     */
    protected function _construct(): void // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_init('product_initial_info', 'product_id');
    }
}
