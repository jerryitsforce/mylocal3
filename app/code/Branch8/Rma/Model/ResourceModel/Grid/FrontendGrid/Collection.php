<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_MpRmaSystem
 * @author    Webkul Software Private Limited
 * @copyright Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\Rma\Model\ResourceModel\Grid\FrontendGrid;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Search\AggregationInterface;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\Collection as DetailsCollection;

/**
 * Class Collection
 * Collection for displaying grid of mprmasystem details.
 */
class Collection extends DetailsCollection implements SearchResultInterface
{
    /**
     * @var AggregationInterface
     */
    protected $aggregations;

    /**
     * @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected $_eventPrefix;

    /**
     * @var string
     */
    protected $_eventObject;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Webkul\SellerSubAccount\Helper\Data
     */
    protected $helper;

    /**
     * Initialize Dependencies
     *
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entity
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $event
     * @param \Magento\Store\Model\StoreManagerInterface $store
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param string $mainTable
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $eventPrefix
     * @param mixed $eventObject
     * @param mixed $resourceModel
     * @param \Magento\Framework\View\Element\UiComponent\DataProvider\Document $model
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     * @return void
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface    $entity,
        \Psr\Log\LoggerInterface                                     $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface                    $event,
        \Magento\Store\Model\StoreManagerInterface                   $store,
        \Magento\Customer\Model\Session                              $customerSession,
        \Magento\Framework\Module\Manager                            $moduleManager,
                                                                     $mainTable,
                                                                     $eventPrefix,
                                                                     $eventObject,
                                                                     $resourceModel,
                                                                     $model = \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
        \Magento\Framework\DB\Adapter\AdapterInterface               $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb         $resource = null
    )
    {
        parent::__construct($entity, $logger, $fetchStrategy, $event, $store, $connection, $resource);
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->moduleManager = $moduleManager;
        $this->_customerSession = $customerSession;
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()
            ->joinLeft(
                ['mu' => 'marketplace_userdata'],
                'mu.seller_id = main_table.seller_id',
                [
                    'salesperson' => "GROUP_CONCAT(DISTINCT mu.salesperson SEPARATOR '|')",
                ]
            )->joinLeft(
                'marketplace_rma_items',
                'main_table.id = marketplace_rma_items.rma_id',
                [
                    'item_id' => 'item_id',
                ]
            )
            ->joinLeft('sales_order_item as soi',
                'marketplace_rma_items.item_id = soi.item_id',
                [
                    'product_name' => "GROUP_CONCAT(DISTINCT soi.name SEPARATOR '<br>') AS product_name",
                    'product_sku' => "GROUP_CONCAT(DISTINCT soi.sku SEPARATOR '<br>') AS product_sku",
                    'supplier_sku' => "GROUP_CONCAT(DISTINCT soi.name SEPARATOR '<br>') AS variantion_sku",

                ]
            )->joinLeft(
                'sales_order',
                'main_table.order_id = sales_order.entity_id',
                [
                    'shipping_method' => 'sales_order.shipping_method',
                    'rma_order_status' => 'sales_order.status',
                    'order_created_at'=>'sales_order.created_at',
                ]
            );
        return $this;
    }

    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'created_at') {
            $field = 'main_table.created_at';
        } elseif ($field === 'sale_person_id') {
            $field = 'salesperson';
        } elseif ($field === 'status') {
            $field = 'main_table.status';
        }elseif ($field === 'product_name'){
            $field = 'soi.name';
        }elseif ($field === 'product_sku'){
            $field = 'soi.sku';
        }elseif ($field === 'supplier_sku'){
            $field = 'soi.option_sku';
        }elseif ($field === 'order_created_at'){
            $field = 'sales_order.created_at';
        }elseif ($field === 'rma_order_status'){
            $field = 'sales_order.status';
        }
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Retrieve all ids for collection Backward compatibility with EAV collection.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function getAllIds($limit = null, $offset = null)
    {
        return $this->getConnection()->fetchCol($this->_getAllIdsSelect($limit, $offset), $this->_bindParams);
    }

    /**
     * Get search criteria.
     *
     * @return null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * Set aggreagte
     *
     * @param AggregationInterface $aggregations
     *
     * @return void
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
    }

    /**
     * Get AggregationInterface
     *
     * @return AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * Set search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return $this
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
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
     * Set items list.
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    /**
     * Set total count.
     *
     * @param int $totalCount
     *
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * Render
     *
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerId = $this->_customerSession->getCustomer()->getId();
        $subsellerModule = $this->moduleManager->isEnabled('Webkul_SellerSubAccount');
        if ($subsellerModule) {
            $this->helper = $objectManager->create(\Webkul\SellerSubAccount\Helper\Data::class);
            if ($this->helper->isSubAccount()) {
                $customerId = $this->helper->getSubAccountSellerId();
            }
        }
        $this->getSelect()->group("main_table.id");
        $this->addFieldToFilter('main_table.seller_id', ['eq' => $customerId]);
        parent::_renderFiltersBefore();
    }
}
