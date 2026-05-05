<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Model\ResourceModel\Order;

use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class SubOrderCollection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    private ParentOrderFactory $parentOrderFactory;
    private RequestInterface $request;

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param $mainTable
     * @param $resourceModel
     * @param $identifierName
     * @param $connectionName
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        EntityFactory    $entityFactory,
        Logger           $logger,
        FetchStrategy    $fetchStrategy,
        EventManager     $eventManager,
        RequestInterface $request,
        string           $mainTable,
        string           $resourceModel = null,
        string           $identifierName = null,
        string           $connectionName = null
    )
    {
        $this->request = $request;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );

    }

    /**
     * @return $this|Collection|void
     */
    protected function _initSelect()
    {
        $this->getSelect()->from(['main_table' => $this->getMainTable()]);
        $this->getSelect()->join(
            ['detail' => $this->getConnection()->getTableName('sales_order_grid')],
            'main_table.children_id = detail.entity_id',
            [
                'increment_id' => 'increment_id',
                'status' => 'status',
                'entity_id' => 'detail.entity_id'
            ]
        );
        $parentId = explode(',', (string)$this->request->getParam('id'));
        $this->getSelect()->where('main_table.parent_id IN (?)', $parentId);
        return $this;
    }
}
