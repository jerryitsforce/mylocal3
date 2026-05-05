<?php
namespace Branch8\AdvancedIsNotRule\Model\ResourceModel\Filter;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Branch8\AdvancedIsNotRule\Model\Filter as FilterModel;
use Branch8\AdvancedIsNotRule\Model\ResourceModel\Filter as FilterResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'filter_id';

    protected function _construct()
    {
        $this->_init(FilterModel::class, FilterResource::class);
    }
}
