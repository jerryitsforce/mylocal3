<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Seller;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Catalog\Model\Product;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Grid extends Extended
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry = null;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Seller\Grid\CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Webkul\Marketplace\Model\ResourceModel\Seller\Grid\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('amrolepremissions_allowed_seller_grid');
        $this->setDefaultSort('seller_id');
        $this->setUseAjax(true);
        if ($this->getRule() && $this->getRule()->getId()) {
            $this->setDefaultFilter(['in_sellers' => 1]);
        }
    }

    public function getRule()
    {
        return $this->coreRegistry->registry('amrolepermissions_current_rule');
    }

    /**
     * @param Column $column
     *
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in role flag
        if ($column->getId() == 'in_sellers') {
            $sellerIds = $this->_getSelectedSellers();
            if (empty($sellerIds)) {
                $sellerIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('seller_id', ['in' => $sellerIds]);
            } else {
                if ($sellerIds) {
                    $this->getCollection()->addFieldToFilter('seller_id', ['nin' => $sellerIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var \Webkul\Marketplace\Model\ResourceModel\Seller\Grid\Collection $collection */
        $collection = $this->collectionFactory->create();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_sellers',
            [
                'type' => 'checkbox',
                'name' => 'in_sellers',
                'values' => $this->_getSelectedSellers(),
                'align' => 'center',
                'index' => 'seller_id',
                'use_index' => true,
                'column_css_class' => 'col-select col-massaction',
                'header_css_class' => 'col-select col-massaction',
            ]
        );

        $this->addColumn(
            'seller_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'seller_id',
                'column_css_class' => 'col-id',
                'header_css_class' => 'col-id',
            ]
        );

        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
                'column_css_class' => 'col-name',
                'header_css_class' => 'col-name',
            ]
        );

        $this->addColumn(
            'email',
            [
                'header' => __('Email'),
                'index' => 'email',
                'column_css_class' => 'col-email',
                'header_css_class' => 'col-email',
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Retrieve grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->_getData(
            'grid_url'
        ) ? : $this->getUrl(
            'branch8_advancedpermissions/seller/allowedGrid',
            ['_current' => true]
        );
    }

    protected function _getSelectedSellers()
    {
        $sellers = $this->getAllowedSellers();
        if (!is_array($sellers)) {
            $sellers = $this->getSelectedRuleSellers();
        }
        return $sellers;
    }

    public function getSelectedRuleSellers()
    {
        if (!$this->getRule()->getSellers()) {
            return [];
        }

        return $this->getRule()->getSellers();
    }
}
