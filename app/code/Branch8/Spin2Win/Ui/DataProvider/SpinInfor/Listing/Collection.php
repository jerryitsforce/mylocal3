<?php
namespace Branch8\Spin2Win\Ui\DataProvider\SpinInfor\Listing;
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
        //   $select->columns(new \Zend_Db_Expr('(select count(*) from spintowin_reports where spintowin_reports.spin_id = main_table.entity_id) as total_drawn'));
        //   $select->columns(new \Zend_Db_Expr('(select count(distinct customer_id) from spintowin_reports where spintowin_reports.spin_id = main_table.entity_id) as total_participants'));
        //   $select->columns(new \Zend_Db_Expr('(select sum(point) from spintowin_redemption where spintowin_redemption.spin_id = main_table.entity_id) as total_point '));
          
      }

    public function addFieldToFilter($field, $condition = null){
        

        return parent::addFieldToFilter($field, $condition);
    }
}