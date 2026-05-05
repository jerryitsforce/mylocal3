<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatAdminUi\Model\ResourceModel\Message\Grid;

use Branch8\WebkulMpBuyerSellerChatAdminUi\Model\ResourceModel\Message\Grid\AggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Session\SessionManager;
use Webkul\MpBuyerSellerChat\Model\ResourceModel\Message\Collection as MessageChatCollection;


/**
 * Class Collection
 * Collection for displaying grid of chat history
 */
class Collection extends MessageChatCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    protected $_aggregations;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var SessionManager
     */
    protected $_session;

    /**
     * @var string
     */
    protected $_eventPrefix;

    /**
     * @var string
     */
    protected $_eventObject;

    /**
     * @param \Magento\Framework\App\Request\Http $request
     * @param SessionManager $session
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactoryInterface
     * @param \Psr\Log\LoggerInterface $loggerInterface
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategyInterface
     * @param ManagerInterface $eventManagerInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param $mainTable
     * @param $eventPrefix
     * @param $eventObject
     * @param $resourceModel
     * @param $model
     * @param $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        SessionManager $session,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactoryInterface,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategyInterface,
        \Magento\Framework\Event\ManagerInterface $eventManagerInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        $model = \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactoryInterface,
            $loggerInterface,
            $fetchStrategyInterface,
            $eventManagerInterface,
            $storeManagerInterface,
            $connection,
            $resource
        );
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($model, $resourceModel);
        $this->request = $request;
        $this->_session = $session;
        $this->setMainTable($mainTable);
    }

    /**
     * @inheritDoc
     */
    public function getAggregations()
    {
        return $this->_aggregations;
    }

    /**
     * @inheritDoc
     */
    public function setAggregations($aggregations)
    {
        $this->_aggregations = $aggregations;
    }

    /**
     * Get search criteria.
     *
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null
    ) {
        return $this;
    }

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Set items list.
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null)
    {
        return $this;
    }
}
