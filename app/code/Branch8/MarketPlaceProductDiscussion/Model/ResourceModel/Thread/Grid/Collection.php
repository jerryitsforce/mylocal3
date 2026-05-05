<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       16/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Thread\Grid;

use Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Thread;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

/**
 * Order grid collection
 */
class Collection extends SearchResult
{
    /**
     * @var string
     */
    protected $_idFieldName = 'thread_id';
    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @param TimezoneInterface|null $timeZone
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        EntityFactory     $entityFactory,
        Logger            $logger,
        FetchStrategy     $fetchStrategy,
        EventManager      $eventManager,
        string            $mainTable = 'branch8_marketplace_product_discussion_thread',
        string            $resourceModel = Thread::class,
        TimezoneInterface $timeZone = null
    )
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->timeZone = $timeZone ?: ObjectManager::getInstance()
            ->get(TimezoneInterface::class);
    }
}
