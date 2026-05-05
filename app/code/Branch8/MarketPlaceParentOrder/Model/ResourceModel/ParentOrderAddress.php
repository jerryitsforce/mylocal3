<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ResourceModel;
class ParentOrderAddress extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sales_parent_order_address', 'entity_id');
    }
}
