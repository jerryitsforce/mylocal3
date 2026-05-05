<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Block;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Cms\Model\ResourceModel\Block\Grid\CollectionFactory;
use Magento\Framework\Registry;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Grid extends Extended
{
    /**
     * @var Registry
     */
    private $coreRegistry = null;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        Context $context,
        Data $backendHelper,
        Registry $coreRegistry,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('amrolepremissions_allowed_block_grid');
        $this->setDefaultSort('block_id');
        $this->setUseAjax(true);
        if ($this->getRule() && $this->getRule()->getId()) {
            $this->setDefaultFilter(['in_blocks' => 1]);
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
        if ($column->getId() == 'in_blocks') {
            $blockIds = $this->_getSelectedBlocks();
            if (empty($blockIds)) {
                $blockIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('block_id', ['in' => $blockIds]);
            } else {
                if ($blockIds) {
                    $this->getCollection()->addFieldToFilter('block_id', ['nin' => $blockIds]);
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
            'in_blocks',
            [
                'type' => 'checkbox',
                'name' => 'in_blocks',
                'values' => $this->_getSelectedBlocks(),
                'align' => 'center',
                'index' => 'block_id',
                'use_index' => true,
                'column_css_class' => 'col-select col-massaction',
                'header_css_class' => 'col-select col-massaction',
            ]
        );

        $this->addColumn(
            'block_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'block_id',
                'column_css_class' => 'col-id',
                'header_css_class' => 'col-id',
            ]
        );

        $this->addColumn(
            'name',
            [
                'header' => __('Title'),
                'index' => 'title',
                'column_css_class' => 'col-name',
                'header_css_class' => 'col-name',
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
            'branch8_advancedpermissions/block/allowedGrid',
            ['_current' => true]
        );
    }

    protected function _getSelectedBlocks()
    {
        $blocks = $this->getAllowedBlocks();
        if (!is_array($blocks)) {
            $blocks = $this->getSelectedRuleBlocks();
        }
        return $blocks;
    }

    public function getSelectedRuleBlocks()
    {
        if (!$this->getRule()->getBlocks()) {
            return [];
        }

        return $this->getRule()->getBlocks();
    }
}
