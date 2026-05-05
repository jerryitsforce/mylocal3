<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\ResourceModel\Message;

use Branch8\HelpDesk\Api\Data\MessageSearchResultInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Message Collection
 */
class Collection extends AbstractCollection implements MessageSearchResultInterface
{
    /**
     * @var string
     */
    protected $_idFieldName = 'message_id';
    /**
     * @var 
     */
    protected $searchCriteria;

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Branch8\HelpDesk\Model\Message',
            'Branch8\HelpDesk\Model\ResourceModel\Message'
        );
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        return $this;
    }

    /**
     * Returns pairs category_id - title
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('message_id', 'title');
    }

    /**
     * Add link attribute to filter.
     *
     * @param string $code
     * @param array $condition
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        $this->performAddStoreFilter($store, $withAdmin);
        return $this;
    }

    /**
     * @param $store
     * @param $withAdmin
     * @return void
     */
    protected function performAddStoreFilter($store, $withAdmin = true)
    {
        if ($store instanceof \Magento\Store\Model\Store) {
            $store = [$store->getId()];
        }

        if (!is_array($store)) {
            $store = [$store];
        }

        if ($withAdmin) {
            $store[] = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        }

        $this->addFilter('store_id', ['in' => $store], 'public');
    }

    /**
     * @param array $items
     * @return $this|Collection
     * @throws \Exception
     */
    public function setItems(array $items)
    {
        if (!$items) {
            return $this;
        }
        foreach ($items as $item) {
            $this->addItem($item);
        }

        return $this;
    }

    /**
     * @return \Magento\Framework\Api\SearchCriteriaInterface
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return void
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $this->searchCriteria = $searchCriteria;
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
}
