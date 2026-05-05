<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'index_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\MarketPlaceParentOrder\Model\ParentOrder::class,
            \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder::class
        );
    }

    /**
     * @param $columns
     * @return $this
     */
    public function joinDetail($columns = '*')
    {
        $detail = $this->getTable('sales_parent_order_detail');
        $this->getSelect()->join(
            $detail . ' as detail',
            'detail.parent_id = main_table.index_id',
            $columns ?: '*'
        );
        return $this;
    }

    /**
     * @param $customerId
     * @return $this
     */
    public function addCustomerFilter($customerId)
    {
        $this->getSelect()->where('detail.customer_id = ? ', (int)$customerId);
        return $this;
    }
}
