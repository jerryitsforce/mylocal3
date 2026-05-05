<?php

namespace Branch8\MarketPlaceParentOrderAdminUi\Model\ResourceModel\ParentOrder\History\Grid;

use Branch8\MarketPlaceParentOrderAdminUi\Model\ResourceModel\ParentOrder\History;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    private RequestInterface $request;

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param RequestInterface $request
     * @param string $mainTable
     * @param string $resourceModel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        EntityFactory    $entityFactory,
        Logger           $logger,
        FetchStrategy    $fetchStrategy,
        EventManager     $eventManager,
        RequestInterface $request,
        string           $mainTable = 'sales_order_status_history',
        string           $resourceModel = History::class
    )
    {
        $this->request = $request;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * @return $this|Collection|void
     */
    public function _initSelect()
    {
        $this->addFilterToMap('order_id', 'main_table.parent_id');
        parent::_initSelect();
        $subIds = explode(',', (string)$this->request->getParam('sub_ids'));
        if (!$subIds) {
            $subIds = [0];
        }
        $this->getSelect()->where('main_table.parent_id IN (?)', $subIds);
        $this->getSelect()->where('entity_name = ?', 'order');
        return $this;
    }

    public function _afterLoad()
    {
        $select = $this->getSelect();
        return parent::_afterLoad();
    }
}
