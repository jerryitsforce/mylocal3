<?php
declare(strict_types=1);

namespace Branch8\MarketplaceParentOrderRetryCancel\Model\ResourceModel;

class FailedRecord extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const STATUS_PENDING = 'pending';
    const STATUS_DONE = 'done';
    const STATUS_FAILED_RECREATE = 'failed_recreate';
    const STATUS_FAILED_CANCEL= 'failed_cancel';

    const STATUS_PROCESSING = 'processing';
    const STATUS_WAITING_INVOICE = 'waiting_invoice';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sales_parent_order_failed_record', 'entity_id');
    }
}
