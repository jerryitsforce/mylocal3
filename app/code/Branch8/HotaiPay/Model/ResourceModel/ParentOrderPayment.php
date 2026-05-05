<?php
declare(strict_types=1);

namespace Branch8\HotaiPay\Model\ResourceModel;
class ParentOrderPayment extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sales_parent_order_payment', 'entity_id');
    }
}
