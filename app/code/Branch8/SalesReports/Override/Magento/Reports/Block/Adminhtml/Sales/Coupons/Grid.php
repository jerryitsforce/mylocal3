<?php
declare(strict_types=1);

namespace Branch8\SalesReports\Override\Magento\Reports\Block\Adminhtml\Sales\Coupons;

/**
 * Adminhtml coupons report grid block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Grid extends \Magento\Reports\Block\Adminhtml\Grid\AbstractGrid
{
    /**
     * GROUP BY criteria
     *
     * @var string
     */
    protected $_columnGroupBy = 'period';

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setCountTotals(true);
        $this->setCountSubTotals(false);
    }

    /**
     * @inheritdoc
     */
    public function getResourceCollectionName()
    {
        if ($this->getFilterData()->getData('report_type') == 'updated_at_order') {
            return \Branch8\SalesReports\Model\ResourceModel\Report\Updatedat\Collection::class;
        } else {
            return \Branch8\SalesReports\Model\ResourceModel\Report\Collection::class;
        }
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->setStoreIds($this->_getStoreIds());
        $currencyCode = $this->getCurrentCurrencyCode();
        $rate = $this->getRate($currencyCode);

        $this->addColumn(
            'period',
            [
                'header' => __('Date'),
                'index' => 'period',
                'sortable' => false,
                'period_type' => $this->getPeriodType(),
                'renderer' => \Magento\Reports\Block\Adminhtml\Sales\Grid\Column\Renderer\Date::class,
                'totals_label' => __('Total'),
                'subtotals_label' => __('Subtotal'),
                'html_decorators' => ['nobr'],
                'header_css_class' => 'col-period',
                'column_css_class' => 'col-period'
            ]
        );
        $this->addColumn(
            'rule_name',
            [
                'header' => __('Rule Name'),
                'index' => 'rule_name',
                'header_css_class' => 'col-rule',
                'column_css_class' => 'col-rule'
            ]
        );
        $this->addColumn(
            'coupon_code',
            [
                'header' => __('Coupon Code'),
                'sortable' => false,
                'index' => 'coupon_code',
                'header_css_class' => 'col-code',
                'column_css_class' => 'col-code'
            ]
        );

        $this->addColumn(
            'coupon_price_rule',
            [
                'header' => __('Price Rule'),
                'sortable' => false,
                'index' => 'coupon_price_rule',
                'header_css_class' => 'col-rule',
                'column_css_class' => 'col-rule'
            ]
        );


        $this->addColumn(
            'overall_orders',
            [
                'header' => __('Order Quantity'),
                'sortable' => false,
                'index' => 'overall_orders',
                'total' => 'sum',
                'type' => 'number',
                'header_css_class' => 'col-overall-orders',
                'column_css_class' => 'col-overall-orders'
            ]
        );

        $this->addColumn(
            'coupon_uses',
            [
                'header' => __('Order Quantity (Discount Code)'),
                'sortable' => false,
                'index' => 'coupon_uses',
                'total' => 'sum',
                'type' => 'number',
                'header_css_class' => 'col-coupon_uses',
                'column_css_class' => 'col-coupon_uses'
            ]
        );
        $this->addColumn(
            'cancellation_orders',
            [
                'header' => __('Total Cancellation Orders'),
                'sortable' => false,
                'index' => 'cancellation_orders',
                'header_css_class' => 'col-cancellation-orders',
                'column_css_class' => 'col-cancellation-orders',
                'total' => 'sum',
                'type' => 'number',
            ]
        );

        $this->addColumn(
            'overall_suggested_retail_price',
            [
                'header' => __('Suggested Retail Price'),
                'sortable' => false,
                'index' => 'overall_suggested_retail_price',
                'header_css_class' => 'col-cancellation-orders',
                'column_css_class' => 'col-cancellation-orders',
                'total' => 'sum',
                'type' => 'currency',
                'currency_code' => $currencyCode,
            ]
        );

        $this->addColumn(
            'overall_product_selling_price',
            [
                'header' => __('Product Selling Price'),
                'sortable' => false,
                'index' => 'overall_product_selling_price',
                'header_css_class' => 'col-overall-product-selling-price-orders',
                'column_css_class' => 'col-overall-product-selling-price-orders',
                'total' => 'sum',
                'type' => 'currency',
                'currency_code' => $currencyCode,
            ]
        );


        $this->addColumn(
            'suggested_retail_price_discounted',
            [
                'header' => __('Suggested Retail Price (Discount Code)'),
                'sortable' => false,
                'index' => 'suggested_retail_price_discounted',
                'header_css_class' => 'col-cancellation-orders',
                'column_css_class' => 'col-cancellation-orders',
                'total' => 'sum',
                'type' => 'currency',
                'currency_code' => $currencyCode,
            ]
        );

        $this->addColumn(
            'product_selling_price_discounted',
            [
                'header' => __('Product Selling Price (Discount Code)'),
                'sortable' => false,
                'index' => 'product_selling_price_discounted',
                'header_css_class' => 'col-product-selling-price-discounted-orders',
                'column_css_class' => 'ccol-product-selling-price-discounted-orders',
                'total' => 'sum',
                'type' => 'currency',
                'currency_code' => $currencyCode,
            ]
        );


        $this->addColumn(
            'discount_amount',
            [
                'header' => __('Shopping Cart Discount'),
                'sortable' => false,
                'type' => 'currency',
                'currency_code' => $currencyCode,
                'total' => 'sum',
                'index' => 'discount_amount',
                'rate' => $rate,
                'header_css_class' => 'col-sales-discount',
                'column_css_class' => 'col-sales-discount',
            ]
        );

        $this->addColumn(
            'vendor_share',
            [
                'header' => __('Vendor Share'),
                'sortable' => false,
                'index' => 'vendor_share',
                'header_css_class' => 'col-vendor-burden-orders',
                'column_css_class' => 'col-vendor-burden-orders',
                'total' => 'sum',
                'type' => 'currency',
                'currency_code' => $currencyCode,
            ]
        );

        $this->addColumn(
            'platform_share',
            [
                'header' => __('Platform Share'),
                'sortable' => false,
                'index' => 'platform_share',
                'header_css_class' => 'col-network-burden-orders',
                'column_css_class' => 'col-network-burden-orders',
                'total' => 'sum',
                'type' => 'currency',
                'currency_code' => $currencyCode,
            ]
        );

        $this->addExportType('*/*/exportCouponsCsv', __('CSV'));
        $this->addExportType('*/*/exportCouponsExcel', __('Excel XML'));

        return parent::_prepareColumns();
    }

    /**
     * Add price rule filter
     *
     * @param \Magento\Reports\Model\ResourceModel\Report\Collection\AbstractCollection $collection
     * @param \Magento\Framework\DataObject $filterData
     * @return \Magento\Reports\Block\Adminhtml\Grid\AbstractGrid
     */
    protected function _addCustomFilter($collection, $filterData)
    {
        if ($filterData->getCouponCodeList()) {
            $couponCodes = explode(',', (string)$filterData->getData('coupon_code_list'));
            if ($couponCodes) {
                $collection->getSelect()->where('coupon_code IN  (?)', $couponCodes);
            }
        }
        if ($filterData->getCouponNameList()) {
            $couponNames = explode(',', (string)$filterData->getData('coupon_name_list'));
            if ($couponNames) {
                $collection->getSelect()->where('rule_name IN  (?)', $couponNames);
            }
        }
        if (!is_null($filterData->getStatus())) {
            $status = $filterData->getStatus();
            if ($filterData->getStatus() == 2) {
                $status = 0;
            }
            $collection->getSelect()->where('salesrule.is_active = ?', $status);
        }
        return parent::_addCustomFilter($filterData, $collection);
    }

    /**
     * @return array|null
     */
    protected function _getAggregatedColumns()
    {
        if ($this->_aggregatedColumns === null) {
            foreach ($this->getColumns() as $column) {
                if (!is_array($this->_aggregatedColumns)) {
                    $this->_aggregatedColumns = [];
                }
                if ($column->hasTotal()) {
                    $expression = "{$column->getTotal()}({$column->getIndex()})";
                    if ($column->getIndex() === 'discount_amount') {
                        $index = 'salesrule_coupon_aggregated.' . $column->getIndex();
                        $expression = "{$column->getTotal()}({$index})";
                    }
                    $this->_aggregatedColumns[$column->getId()] = $expression;
                }
            }
        }
        return $this->_aggregatedColumns;
    }

    /**
     * @return $this|Grid|\Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareCollection()
    {
        $filterData = $this->getFilterData();
        if ($filterData->getData('from') == null || $filterData->getData('to') == null) {
            $this->setCountTotals(false);
            $this->setCountSubTotals(false);
            return parent::_prepareCollection();
        }
        $storeIds = $this->_getStoreIds();
        $resourceCollection = $this->_resourceFactory->create(
            $this->getResourceCollectionName()
        )->setPeriod(
            $filterData->getData('period_type') ? $filterData->getData('period_type') : 'day'
        )->setDateRange(
            $filterData->getData('from', null),
            $filterData->getData('to', null)
        )->addStoreFilter(
            $storeIds
        )->setAggregatedColumns(
            $this->_getAggregatedColumns()
        );
        $this->_addCustomFilter($resourceCollection, $filterData);
        if ($this->_isExport) {
            $this->setCollection($resourceCollection);
            return $this;
        }

        if ($this->getCountSubTotals()) {
            $this->getSubTotals();
        }
        if ($this->getCountTotals()) {
            $totalsCollection = $this->_resourceFactory->create(
                $this->getResourceCollectionName()
            )->setPeriod(
                $filterData->getData('period_type')
            )->setDateRange(
                $filterData->getData('from', null),
                $filterData->getData('to', null)
            )->addStoreFilter(
                $storeIds
            )->setAggregatedColumns(
                $this->_getAggregatedColumns()
            )->isTotals(
                true
            );
            $this->_addCustomFilter($totalsCollection, $filterData);
            foreach ($totalsCollection as $item) {
                $this->setTotals($item);
                break;
            }
        }
        $this->getCollection()->setColumnGroupBy($this->_columnGroupBy);
        $this->getCollection()->setResourceCollection($resourceCollection);
        return \Magento\Backend\Block\Widget\Grid\Extended::_prepareCollection();
    }
}
