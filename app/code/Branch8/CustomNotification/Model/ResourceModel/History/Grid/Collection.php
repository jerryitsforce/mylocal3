<?php

declare(strict_types=1);

namespace Branch8\CustomNotification\Model\ResourceModel\History\Grid;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * Constructor.
     *
     * @param RequestInterface $request
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string|null $resourceModel
     * @param string|null $identifierName
     * @param string|null $connectionName
     *
     * @throws LocalizedException
     */
    public function __construct(
        RequestInterface $request,
        EntityFactory    $entityFactory,
        Logger           $logger,
        FetchStrategy    $fetchStrategy,
        EventManager     $eventManager,
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
     * @inheritdoc
     */
    protected function _initSelect(): void
    {
        parent::_initSelect();

        $select = $this->getSelect();

        $select->joinLeft(
            ['ce' => $this->getTable('customer_grid_flat')],
            'main_table.customer_id = ce.entity_id',
            ['name', 'email']
        );

        $notificationId = $this->getNotificationId();
        if ($notificationId) {
            $this->addFieldToFilter('notification_id', $notificationId);
        }

//        $select->group('main_table.customer_id');
//        $select->columns(['max_entity_id' => new \Zend_Db_Expr('MAX(main_table.entity_id)')]);
    }

    /**
     * Get current notification ID.
     *
     * @return int
     */
    private function getNotificationId(): int
    {
        $notificationId = (int)$this->request->getParam('id');
        if (empty($notificationId)) {
            $notificationId = (int)$this->request->getParam('notification_id');
        }
        return $notificationId;
    }

    public function addFieldToFilter($field, $condition = null)
    {
      if($field == 'entity_id') {
          $field = 'main_table.entity_id';
      }
      return parent::addFieldToFilter($field, $condition);
    }
}
