<?php

namespace Branch8\MagentoVisualMerchandiser\Model\ResourceModel\RuleIndex;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\MagentoVisualMerchandiser\Model\RuleIndex::class,
            \Branch8\MagentoVisualMerchandiser\Model\ResourceModel\RuleIndex::class);
    }
}
