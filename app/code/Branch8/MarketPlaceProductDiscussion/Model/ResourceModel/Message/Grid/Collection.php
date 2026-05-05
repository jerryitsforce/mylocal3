<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       16/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Message\Grid;

use Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Message;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
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
    protected $_idFieldName = 'message_id';
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
        string            $mainTable = 'branch8_marketplace_product_discussion_message',
        string            $resourceModel = Message::class,
        TimezoneInterface $timeZone = null,
        RequestInterface  $request = null
    )
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->timeZone = $timeZone ?: ObjectManager::getInstance()
            ->get(TimezoneInterface::class);
        $this->request = $request ?: ObjectManager::getInstance()->get(RequestInterface::class);
    }

    /**
     * @return $this|Collection
     */
    protected function _beforeLoad()
    {
        parent::_beforeLoad();
        $subIds = explode(',', (string)$this->request->getParam('thread_id'));
        if (!$subIds) {
            $subIds = [0];
        }
        $this->getSelect()->where('main_table.thread_id IN (?)', $subIds);
        return $this;
    }
}
