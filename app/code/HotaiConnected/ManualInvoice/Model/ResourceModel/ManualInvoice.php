<?php
declare(strict_types=1);

namespace HotaiConnected\ManualInvoice\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ManualInvoice extends AbstractDb
{
    private const TABLE_NAME = 'manual_invoice';
    private const PRIMARY_KEY = 'entity_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }
}