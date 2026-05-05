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

namespace Branch8\RmaAdminUi\Model\ResourceModel\Details\Grid;

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
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entity
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Store\Model\StoreManagerInterface $store
     * @param \Magento\Framework\Event\ManagerInterface $event
     * @param string $mainTable
     * @param string $eventPrefix
     * @param string $eventObject
     * @param string $resourceModel
     * @param string $model
     * @param \Magento\Framework\DB\Adapter\AdapterInterfac|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface    $entity,
        \Psr\Log\LoggerInterface                                     $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Store\Model\StoreManagerInterface                   $store,
        \Magento\Framework\Event\ManagerInterface                    $event,
        string                                                       $mainTable,
        string                                                       $eventPrefix,
        string                                                       $eventObject,
        string                                                       $resourceModel,
        string                                                       $model = \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
        \Magento\Framework\DB\Adapter\AdapterInterface               $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb         $resource = null
    )
    {
        parent::__construct($entity, $logger, $fetchStrategy, $event, $store, $connection, $resource);
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
    }

    /***
     * @return $this|Collection|void
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        return $this;
    }

   /* public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'created_at') {
            $field = 'main_table.created_at';
        } elseif ($field === 'sale_person_id') {
            $field = 'salesperson';
        } elseif ($field === 'status') {
            $field = 'main_table.status';
        } elseif ($field === 'seller_id') {
            $field = 'main_table.seller_id';
        } elseif ($field === 'product_name') {
            $field = 'soi.name';
        } elseif ($field === 'product_sku') {
            $field = 'soi.sku';
        } elseif ($field === 'supplier_sku') {
            $field = 'soi.option_sku';
        } elseif ($field === 'order_created_at') {
            $field = 'sales_order.created_at';
        } elseif ($field === 'rma_order_status') {
            $field = 'sales_order.status';
        }
        return parent::addFieldToFilter($field, $condition);
    }*/

  /*  public function addOrder($field, $order = null)
    {
        if ($field === 'created_at') {
            $field = 'main_table.created_at';
        } elseif ($field === 'supplier_sku') {
            $field = 'soi.option_sku';
        } elseif ($field === 'product_name') {
            $field = 'soi.name';
        } elseif ($field === 'product_sku') {
            $field = 'soi.sku';
        }
        return parent::addOrder($field, $order);
    }*/

    /**
     * Set AggregationInterface
     *
     * @param AggregationInterface $aggregations
     *
     * @return $this
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
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
     * Get AggregationInterface
     *
     * @return AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
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
     *
     * @return $this
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
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
}
