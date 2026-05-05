<?php
namespace Branch8\CatalogRule\Ui\DataProvider\CatalogRuleHistory\Listing;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{

    protected $request;

    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable,
        \Magento\Framework\App\RequestInterface $request,
        $resourceModel = null,
        $identifierName = null,
        $connectionName = null
    )
    {
        $this->request = $request;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager,
            $mainTable, $resourceModel, $identifierName, $connectionName);
        
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        $select = $this->getSelect();
        // $select->columns(['createdAt' => 'main_table.created_at']);
        // $select->joinLeft(['appr' => 'catalogrule_approval'], 'appr.entity_id = main_table.approval_id', []);
        $select->order('main_table.entity_id desc');
    }
}
