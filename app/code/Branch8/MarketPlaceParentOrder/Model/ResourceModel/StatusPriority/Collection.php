<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ResourceModel\StatusPriority;

use Branch8\MarketPlaceParentOrder\Model\ResourceModel\StatusPriority as StatusPriorityResource;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

/**
 * Order grid collection
 */
class Collection extends SearchResult
{
    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger        $logger,
        FetchStrategy $fetchStrategy,
        EventManager  $eventManager,
        string        $mainTable = 'sales_order_status',
        string        $resourceModel = StatusPriorityResource::class
    )
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * @return $this|Collection|void
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->joinPriorityTable();
        return $this;
    }

    /**
     * @return $this
     */
    public function joinPriorityTable()
    {
        if (!$this->getFlag('status_joined')) {
            $this->getSelect()->joinLeft(
                ['priority_table' => $this->getTable('sales_parent_order_status_priority')],
                'main_table.status=priority_table.status',
                ['priority']
            )->order('priority ASC');
            $this->setFlag('status_joined', true);
        }
        return $this;
    }
}
