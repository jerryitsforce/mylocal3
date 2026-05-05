<?php

namespace Branch8\CityDirectory\Model\ResourceModel\Collection;

use Branch8\CityDirectory\Model\RomCity as RomCityModel;
use Branch8\CityDirectory\Model\ResourceModel\RomCity  as RomCityResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    //@codingStandardsIgnoreLine
    protected $_idCity = 'entity_id';

    /**
     * Init resource model
     * @return void
     */
    public function _construct()
    {

        $this->_init(
            RomCityModel::class,
            RomCityResourceModel::class
        );
        $this->_map['romcity']['entity_id'] = 'main_table.entity_id';
    }

    /**
     * Add filter by store
     *
     * @param int|array|\Magento\Store\Model\Store $store
     * @param bool $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        return $this;
    }
}