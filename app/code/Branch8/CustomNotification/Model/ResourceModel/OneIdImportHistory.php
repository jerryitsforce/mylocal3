<?php

namespace Branch8\CustomNotification\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class OneIdImportHistory extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('magenest_notification_oneid_import_history', 'entity_id');
    }
}

