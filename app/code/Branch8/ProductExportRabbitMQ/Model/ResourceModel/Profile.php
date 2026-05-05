<?php

namespace Branch8\ProductExportRabbitMQ\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Profile extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('branch8_product_export_profile', 'entity_id');
    }
}
