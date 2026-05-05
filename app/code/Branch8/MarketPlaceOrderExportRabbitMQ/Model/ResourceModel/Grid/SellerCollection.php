<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Grid;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile;
use Psr\Log\LoggerInterface as Logger;
use Zend_Db_Select;

/**
 * Order grid collection
 */
class SellerCollection extends SearchResult
{
    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * Initialize dependencies.
     *
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @param TimezoneInterface|null $timeZone
     */
    public function __construct(
        EntityFactory     $entityFactory,
        Logger            $logger,
        FetchStrategy     $fetchStrategy,
        EventManager      $eventManager,
        string            $mainTable = 'branch8_order_export_profile',
        string            $resourceModel = Profile::class,
        TimezoneInterface $timeZone = null
    )
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->timeZone = $timeZone ?: ObjectManager::getInstance()
            ->get(TimezoneInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $totalFiles = new \Zend_Db_Expr(
            '(SELECT count(*) FROM branch8_order_export_profile_batches WHERE entity_id=parent_id)'
        );
        $totalCompletes = new \Zend_Db_Expr(
            '(SELECT count(*) FROM branch8_order_export_profile_batches WHERE entity_id=parent_id and batch_status="done")'
        );
        $this->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns(
            [

                'entity_id' => 'entity_id',
                'status' => 'status',
                'receiver_email' => 'receiver_email',
                'receiver_name' => 'receiver_name',
                'email_sent' => 'email_sent',
                'total_files' => $totalFiles,
                'total_completed' => $totalCompletes,
                'publish_at' => 'publish_at',
                'created_at' => 'created_at'
            ]
        );
        return $this;
    }
}
