<?php

namespace Branch8\FlagshipStore\Ui\DataProvider\FlagshipStore\Listing;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        TimezoneInterface   $_timezone,
        $mainTable,
        $resourceModel = null,
        $identifierName = null,
        $connectionName = null
    )
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager,
            $mainTable, $resourceModel, $identifierName, $connectionName);
    }

    /**
     * @return void
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $select = $this->getSelect();
        $select->joinLeft(['fss' => 'flagship_store_seller'], 'fss.flagship_store_id = main_table.entity_id ', []);
        $select->joinLeft(['mp_data' => 'marketplace_userdata'], 'mp_data.seller_id = fss.seller_id', ['sellers' => 'group_concat(mp_data.seller_code)']);
        $select->joinLeft(['mp_data_main' => 'marketplace_userdata'], 'mp_data_main.seller_id = main_table.main_seller', ['main_seller_code' => 'mp_data_main.seller_code']);
        $select->group('main_table.entity_id');
        $select->order('main_table.entity_id desc');
    }

    public function addFieldToFilter($field, $condition = null){
        if ($field == 'entity_id') {
            $field = 'main_table.entity_id';
        }
        var_dump($field, $condition);die;
        return parent::addFieldToFilter($field, $condition);
    }
}