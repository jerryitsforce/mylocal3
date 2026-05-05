<?php

namespace Branch8\BrandManagement\Model\ResourceModel\BrandOption\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Brand Option Grid Collection
 */
class Collection extends SearchResult
{
    /**
     * Initialize select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        $this->addFieldToSelect(['option_id', 'sort_order', 'value']); // 添加 'value'
        $this->setMainTable('eav_attribute_option');
        
        $this->getSelect()
            ->join(
                ['eaov' => $this->getTable('eav_attribute_option_value')],
                'main_table.option_id = eaov.option_id',
                ['value' => 'eaov.value']
            )
            ->join(
                ['ea' => $this->getTable('eav_attribute')],
                'ea.attribute_id = main_table.attribute_id',
                []
            )
            ->where('ea.attribute_code = ?', 'brand')
            ->where('ea.entity_type_id = ?', 4)
            ->where('eaov.store_id = ?', 0)
            ->order('main_table.sort_order ASC');
        
       
        return $this;
    }
}