<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\ResourceModel\Category;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 *Ticket Category Collection
 */
class Collection extends AbstractCollection implements OptionSourceInterface, SearchResultInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var string
     */
    protected $_idFieldName = 'category_id';

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface    $entityFactory,
        \Psr\Log\LoggerInterface                                     $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        ManagerInterface                                             $eventManager,
        StoreManagerInterface                                        $storeManager,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb         $resource = null,
        \Magento\Framework\DB\Adapter\AdapterInterface               $connection = null

    )
    {
        $this->storeManager = $storeManager;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Branch8\HelpDesk\Model\Category',
            'Branch8\HelpDesk\Model\ResourceModel\Category'
        );
        $this->_map['fields']['page_id'] = 'main_table.page_id';
        $this->_map['fields']['store_id'] = 'store_table.store_id';
    }

    /**
     * Returns pairs category_id - title
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('category_id', 'title');
    }


    /**
     * Add filter by store
     *
     * @param int|array|\Magento\Store\Model\Store $store
     * @param bool $withAdmin
     *
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if ($store instanceof Store) {
            $store = [$store->getId()];
        }

        if (!is_array($store)) {
            $store = [$store];
        }

        if ($withAdmin) {
            $store[] = Store::DEFAULT_STORE_ID;
        }

        $this->addFilter('store_id', ['in' => $store], 'public');

        return $this;
    }

    /**
     * Join store relation table if there is store filter
     *
     * @param string $tableName
     * @param string|null $linkField
     *
     * @return void
     */
    protected function joinStoreRelationTable($tableName, $linkField)
    {
        if ($this->getFilter('store_id')) {
            $this->getSelect()->join(
                ['store_table' => $this->getTable($tableName)],
                'main_table.' . $linkField . ' = store_table.' . $linkField,
                []
            )->group(
                'main_table.' . $linkField
            );
        }

        parent::_renderFiltersBefore();
    }

    /**
     * Perform operations before rendering filters
     *
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        $this->joinStoreRelationTable(
            'branch8_helpdesk_category_store', 'category_id'
        );
    }

    /**
     * @param array|string $field
     * @param null $condition
     *
     * @return $this|Collection
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'store_id') {
            return $this->addStoreFilter($condition, false);
        }
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * @param string $linkField
     * @param array $linkedIds
     *
     * @return array
     */
    protected function getStoreData($linkField, $linkedIds)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['branch8_helpdesk_category_store' => $this->getTable('branch8_helpdesk_category_store')])
            ->where('branch8_helpdesk_category_store.' . $linkField . ' IN (?)', $linkedIds);
        $result = $connection->fetchAll($select);

        $storesData = [];
        foreach ($result as $storeData) {
            $storesData[$storeData[$linkField]][] = $storeData['store_id'];
        }
        return $storesData;
    }

    /**
     * @return AbstractCollection
     */
    protected function _afterLoad()
    {
        $linkField = 'category_id';
        $linkedIds = $this->getColumnValues($linkField);
        if (count($linkedIds)) {
            $storesData = $this->getStoreData($linkField, $linkedIds);
            if ($storesData) {
                foreach ($this->getItems() as $item) {
                    $linkedId = $item->getData($linkField);
                    if (!isset($storesData[$linkedId])) {
                        continue;
                    }

                    list($storeId, $storeCode) = $this->getStoreDataForItem($storesData[$linkedId]);
                    $item->setData('_first_store_id', $storeId);
                    $item->setData('store_code', $storeCode);
                    $item->setData('store_id', $storesData[$linkedId]);
                }
            }
        }

        return parent::_afterLoad();
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStoreDataForItem($data)
    {
        $storeIdKey = array_search(Store::DEFAULT_STORE_ID, $data, true);
        if ($storeIdKey !== false) {
            $stores = $this->storeManager->getStores(false, true);
            $storeId = current($stores)->getId();
            $storeCode = key($stores);
        } else {
            $storeId = current($data);
            $storeCode = $this->storeManager->getStore($storeId)->getCode();
        }

        return [$storeId, $storeCode];
    }

    /**
     * @inheritdoc
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @inheritdoc
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
    }

    /**
     * @return null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
     * @return $this|Collection
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * @param $totalCount
     * @return $this|Collection
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * @param array|null $items
     * @return $this|Collection
     */
    public function setItems(array $items = null)
    {
        return $this;
    }
}
