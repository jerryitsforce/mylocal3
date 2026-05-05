<?php
namespace Branch8\EventTicket\Ui\DataProvider\TicketEvent\Listing;

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
          $select = $this->getSelect();
          $select->columns(['createdAt' => 'main_table.created_at']);
          $select->joinLeft(['seller' => 'marketplace_userdata'], 'seller.seller_id = main_table.seller_id', ['seller_code']);
          $select->joinLeft(['sr' => 'ticket_event_ticket'], 'sr.event_id = main_table.entity_id', ['num_serial' => 'count(serial_number)'])
              ->group('main_table.entity_id');
      }

    public function addFieldToFilter($field, $condition = null){
        if ($field == 'entity_id') {
            $field = 'main_table.entity_id';
        }
        if ($field == 'createdAt') {
            $field = 'main_table.created_at';
            if(isset($condition['gteq'])){
                $condition['gteq'] = $this->timezone->convertConfigTimeToUtc($condition['gteq']);
            }
            if(isset($condition['lteq'])){
                $condition['lteq'] = $this->timezone->convertConfigTimeToUtc($condition['lteq']);
            }
        }

        return parent::addFieldToFilter($field, $condition);
    }
}
