<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\Mmegamenu\Model\ResourceModel\Mmegamenu;

/**
 * Mmegamenu resource model collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Init resource collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\Mmegamenu\Model\Mmegamenu', 'Branch8\Mmegamenu\Model\ResourceModel\Mmegamenu');
    }

    public function addStoreFilter($store, $adminStore = true) {
        $stores = array();

        if ($store instanceof \Magento\Store\Model\Store) {
            $stores[] = (int)$store->getId();
        }

        $stores[] = 0;
        $storeTable = $this->getTable('branch8_megamenu_store');
        $this->getSelect()->join(
            array('stores' => $storeTable),
            'main_table.megamenu_id = stores.megamenu_id',
            array()
        )
            ->where('stores.store_id in (?)', ($adminStore ? $stores : $store));
        return $this;
    }

    /**
     * join table to get Stores of Banner
     * @return $this
     */
    public function joinStoreValue()
    {
        $this->getSelect()->joinLeft(
            ['store_table' => $this->getTable('branch8_megamenu_store')],
            'main_table.megamenu_id = store_table.megamenu_id',
            ['store_ids' => 'GROUP_CONCAT(store_table.store_id)']
        )->group('main_table.megamenu_id');

        return $this;
    }

    protected function _renderFiltersBefore()
    {
        $this->joinStoreValue();

        parent::_renderFiltersBefore();
    }

    protected function _initSelect()
    {

        $this->addFilterToMap('store_ids', 'store_table.store_id');

        parent::_initSelect();
    }
}
