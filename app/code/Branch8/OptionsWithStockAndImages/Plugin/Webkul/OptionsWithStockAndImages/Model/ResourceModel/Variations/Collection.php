<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Webkul\OptionsWithStockAndImages\Model\ResourceModel\Variations;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    public $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            \Webkul\OptionsWithStockAndImages\Model\Variations::class,
            \Webkul\OptionsWithStockAndImages\Model\ResourceModel\Variations::class
        );
        $this->_map['fields']['entity_id'] = 'main_table.entity_id';
    }
    
    /**
     * Retrieve clear select
     *
     * @return \Magento\Framework\DB\Select
     */
    protected function _getClearSelect()
    {
        return $this->_buildClearSelect();
    }

    /**
     * Build clear select
     *
     * @param \Magento\Framework\DB\Select $select
     * @return \Magento\Framework\DB\Select
     */
    protected function _buildClearSelect($select = null)
    {
        if (null === $select) {
            $select = clone $this->getSelect();
        }
        $select->reset(
            \Magento\Framework\DB\Select::ORDER
        );
        $select->reset(
            \Magento\Framework\DB\Select::LIMIT_COUNT
        );
        $select->reset(
            \Magento\Framework\DB\Select::LIMIT_OFFSET
        );
        $select->reset(
            \Magento\Framework\DB\Select::COLUMNS
        );

        return $select;
    }

    public function aroundGetVariationsIdsByOptionValue(
        \Webkul\OptionsWithStockAndImages\Model\ResourceModel\Variations\Collection $subject,
        \Closure $proceed,
        $title, $limit = null, $offset = null
    ) {
        if($title == null) {
            return [];
        }
        $title = str_replace('\\','\\\\', $title);
        $title = str_replace('"','\"', $title);
        $idsSelect = $this->_getClearSelect();
        $idsSelect->columns(['entity_id', 'image','product_id','price','comb']);
        $idsSelect->orWhere($this->getConnection()->quoteInto('main_table.comb like (?)', $title.'_%'));
        $idsSelect->orWhere($this->getConnection()->quoteInto('main_table.comb like (?)', '%_'.$title));
        $idsSelect->orWhere($this->getConnection()->quoteInto('main_table.comb like (?)', $title));
        $idsSelect->orWhere($this->getConnection()->quoteInto('main_table.comb like (?)', '%'.$title.'%'));
        //var_dump($idsSelect->__toString());
        $idsSelect->limit($limit, $offset);
        $idsSelect->resetJoinLeft();
        return $this->getConnection()->fetchAll($idsSelect, $this->_bindParams);
    }
}
