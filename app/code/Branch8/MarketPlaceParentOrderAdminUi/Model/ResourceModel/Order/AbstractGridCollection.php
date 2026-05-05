<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Model\ResourceModel\Order;

use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class AbstractGridCollection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    private ParentOrderFactory $parentOrderFactory;
    private RequestInterface $request;

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param RequestInterface $request
     * @param ParentOrderFactory $parentOrderFactory
     * @param string $mainTable
     * @param string|null $resourceModel
     * @param string|null $identifierName
     * @param string|null $connectionName
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        EntityFactory      $entityFactory,
        Logger             $logger,
        FetchStrategy      $fetchStrategy,
        EventManager       $eventManager,
        RequestInterface   $request,
        ParentOrderFactory $parentOrderFactory,
        string             $mainTable,
        string             $resourceModel = null,
        string             $identifierName = null,
        string             $connectionName = null
    )
    {
        $this->request = $request;
        $this->parentOrderFactory = $parentOrderFactory;
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
        $subIds = [0];
        if ($requestSubIds = $this->getSubIds()) {
            $subIds = $requestSubIds;
        }
        $this->getSelect()->where('order_id IN (?)', $subIds);
        return $this;
    }

    /**
     * @return \Branch8\MarketPlaceParentOrder\Model\ParentOrder
     */
    private function getSubIds()
    {
        try {
            return explode(',', (string)$this->request->getParam('sub_ids'));
        } catch (\Exception $exception) {
            return [0];
        }
    }
}
