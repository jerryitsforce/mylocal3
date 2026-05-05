<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       20/03/2026
 */

namespace Branch8\BlackListKeyWords\Model\ResourceModel\Keyword\Grid;

use Branch8\BlackListKeyWords\Model\ResourceModel\Keyword;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

class Collection extends SearchResult
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';
    /**
     * @var TimezoneInterface
     */
    private $timeZone;
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @param TimezoneInterface|null $timeZone
     * @param RequestInterface|null $request
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(

        EntityFactory     $entityFactory,
        Logger            $logger,
        FetchStrategy     $fetchStrategy,
        EventManager      $eventManager,
        string            $mainTable = 'branch8_blacklist_keywords',
        string            $resourceModel = Keyword::class,
        TimezoneInterface $timeZone = null,
        RequestInterface  $request = null
    )
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->timeZone = $timeZone ?: ObjectManager::getInstance()
            ->get(TimezoneInterface::class);
        $this->request = $request ?: ObjectManager::getInstance()->get(RequestInterface::class);
    }
}
