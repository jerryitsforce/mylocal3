<?php
namespace Branch8\RewardSystem\Ui\DataProvider\Event\Listing;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    protected $timezone;

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
        $this->timezone = $_timezone;
    }

    /**
     * @return void
     */
      protected function _initSelect()
      {
          parent::_initSelect();
      
      }

    public function addFieldToFilter($field, $condition = null){
        

        return parent::addFieldToFilter($field, $condition);
    }
}
