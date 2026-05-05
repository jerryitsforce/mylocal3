<?php
declare(strict_types=1);

namespace Branch8\SalesReports\Model\ResourceModel\Report;

/**
 * Sales report coupons collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Collection extends \Magento\Sales\Model\ResourceModel\Report\Collection\AbstractCollection
{
    /**
     * Period format for report (day, month, year)
     *
     * @var string
     */
    protected $_periodFormat;

    /**
     * Aggregated Data Table
     *
     * @var string
     */
    protected $_aggregationTable = 'salesrule_coupon_aggregated';

    /**
     * Array of columns that should be aggregated
     *
     * @var array
     */
    protected $_selectedColumns = [];

    /**
     * Array where rules ids stored
     *
     * @var array
     */
    protected $_rulesIdsFilter;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Report\RuleFactory $ruleFactory
     */
    protected $_ruleFactory;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\SalesRule\Model\ResourceModel\Report\RuleFactory $ruleFactory
     * @param \Magento\Sales\Model\ResourceModel\Report $resource
     * @param mixed $connection
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory             $entityFactory,
        \Psr\Log\LoggerInterface                                     $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface                    $eventManager,
        \Magento\Sales\Model\ResourceModel\Report                    $resource,
        \Magento\SalesRule\Model\ResourceModel\Report\RuleFactory    $ruleFactory,
        \Magento\Framework\DB\Adapter\AdapterInterface               $connection = null
    )
    {
        $this->_ruleFactory = $ruleFactory;
        $resource->init($this->_aggregationTable);
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $resource, $connection);
    }

    /**
     * @return void
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $select = $this->getSelect();
        $select->joinLeft(['salesrule' => 'salesrule'],
            'salesrule_coupon_aggregated.coupon_rule_id = salesrule.rule_id', ['is_active']
        );
    }

    /**
     * Collect columns for collection
     *
     * @return array
     */
    protected function _getSelectedColumns()
    {
        $connection = $this->getConnection();
        if ('month' == $this->_period) {
            $this->_periodFormat = $connection->getDateFormatSql('period', '%Y-%m');
        } elseif ('year' == $this->_period) {
            $this->_periodFormat = $connection->getDateExtractSql(
                'period',
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_YEAR
            );
        } else {
            $this->_periodFormat = $connection->getDateFormatSql('period', '%Y-%m-%d');
        }
        /**
         * Date: Displays the date
         * Rule Name
         * Coupon Code
         * Price Rule
         * Order Quantity
         * Order Quantity (Discount Code)
         * Cancellation/Return Quantity:
         * Suggested Retail Price:
         * Product Selling Price:
         * Suggested Retail Price (Discount Code)
         * Product Selling Price (Discount Code)
         * Shopping Cart Discount
         * Vendor Burden
         * Network Burden
         */
        if (!$this->isTotals() && !$this->isSubTotals()) {
            $this->_selectedColumns = [
                'period' => $this->_periodFormat,
                'rule_name' => 'rule_name',
                'coupon_code' => 'coupon_code',
                'coupon_price_rule' => 'coupon_price_rule',
                'overall_orders' => 'overall_orders',
                'coupon_uses' => 'coupon_uses',
                'cancellation_orders' => 'cancellation_orders',
                'overall_suggested_retail_price' => 'overall_suggested_retail_price',
                'overall_product_selling_price' => 'overall_product_selling_price',
                'suggested_retail_price_discounted' => 'suggested_retail_price_discounted',
                'product_selling_price_discounted' => 'product_selling_price_discounted',
                'discount_amount' => 'salesrule_coupon_aggregated.discount_amount',
                'vendor_share' => 'vendor_share',
                'platform_share' => 'platform_share',
                'is_active' => 'salesrule.is_active'
            ];
        }

        if ($this->isTotals()) {
            $this->_selectedColumns = $this->getAggregatedColumns();
        }

        if ($this->isSubTotals()) {
            $this->_selectedColumns = $this->getAggregatedColumns() + ['period' => $this->_periodFormat];
        }

        return $this->_selectedColumns;
    }

    /**
     * Add selected data
     *
     * @return Collection
     */
    protected function _applyAggregatedTable()
    {
        $this->getSelect()->from($this->getResource()->getMainTable(), $this->_getSelectedColumns());
        if ($this->isSubTotals()) {
            $this->getSelect()->group($this->_periodFormat);
        } elseif (!$this->isTotals()) {
            $this->getSelect()->group(
                [
                    $this->_periodFormat,
                    'coupon_code',
                ]
            );
        }

        return parent::_applyAggregatedTable();
    }

    /**
     * Add filtering by rules ids
     *
     * @param array $rulesList
     * @return Collection
     */
    public function addRuleFilter(array $rulesList)
    {
        $this->_rulesIdsFilter = $rulesList;
        return $this;
    }

    /**
     * Apply filtering by rules ids
     *
     * @return $this
     */
    protected function _applyRulesFilter()
    {
        if (empty($this->_rulesIdsFilter) || !is_array($this->_rulesIdsFilter)) {
            return $this;
        }

        $rulesList = $this->_ruleFactory->create()->getUniqRulesNamesList();

        $rulesFilterSqlParts = [];
        foreach ($this->_rulesIdsFilter as $ruleId) {
            if (!isset($rulesList[$ruleId])) {
                continue;
            }
            $ruleName = $rulesList[$ruleId];
            $rulesFilterSqlParts[] = $this->getConnection()->quoteInto('rule_name = ?', $ruleName);
        }

        if (!empty($rulesFilterSqlParts)) {
            $this->getSelect()->where(implode(' OR ', $rulesFilterSqlParts));
        }
        return $this;
    }

    /**
     * Apply collection custom filter
     *
     * @return \Magento\Sales\Model\ResourceModel\Report\Collection\AbstractCollection
     */
    protected function _applyCustomFilter()
    {
        $this->_applyRulesFilter();
        return parent::_applyCustomFilter();
    }

    public function getItems()
    {
        parent::getItems();
        return $this->_items;
    }
}
