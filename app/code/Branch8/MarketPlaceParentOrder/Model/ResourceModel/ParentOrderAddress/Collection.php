<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderAddress;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress::class,
            \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderAddress::class
        );
    }
}
