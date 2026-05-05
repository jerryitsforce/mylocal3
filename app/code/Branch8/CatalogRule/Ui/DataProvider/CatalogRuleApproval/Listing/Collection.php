<?php
namespace Branch8\CatalogRule\Ui\DataProvider\CatalogRuleApproval\Listing;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
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

    protected function _initSelect()
    {
        parent::_initSelect();
        $select = $this->getSelect();
        $select->columns(['createdAt' => 'main_table.created_at', 'updatedAt' => 'main_table.updated_at']);
        $select->joinLeft(['catalogrule' => 'catalogrule'], 'catalogrule.rule_id = main_table.catalogrule_id', ['name']);
        $select->order('status asc');
        $select->where('status = '.\Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_UNDER_REVIEW);
        $select->order('entity_id desc');
    }

    public function addFieldToFilter($field, $condition = null){
        if($field == 'createdAt'){
            $field = 'created_at';
        }

        return parent::addFieldToFilter($field, $condition);
    }

}
