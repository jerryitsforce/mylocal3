<?php

namespace Branch8\Customer\Block\Adminhtml\Organization;

class Organization extends \Magento\Backend\Block\Widget\Grid\Extended{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory $organizationCollectionFactory,
        array $data = []
    ){
        parent::__construct($context, $backendHelper, $data);
        $this->organizationCollectionFactory = $organizationCollectionFactory;
    }

    /**
     * Initialize grid
     *
     * @return void
     */
    protected function _construct(){
        parent::_construct();
        $this->setId('organizationGrid');
        $this->setDefaultSort('entity_id');
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Review\Block\Adminhtml\Grid
     */
    protected function _prepareCollection(){
        /** @var $collection \Branch8\Customer\Model\ResourceModel\OrganizationHistory\Collection */
        $collection = $this->organizationCollectionFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    /**
     * Prepare grid columns
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareColumns(){
        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'index' => 'entity_id',
                'type' => 'text',
                'truncate' => 50,
                'escape' => true,
                'sortable' => true,
                'filter' => true
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Frontend Name'),
                'index' => 'name',
                'type' => 'text',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name',
                'sortable' => true,
                'filter' => true
            ]
        );
        $this->addColumn(
            'hotai1_name',
            [
                'header' => __('Mapping API Name'),
                'type' => 'text',
                'index' => 'hotai1_name',
                'header_css_class' => 'col-date',
                'column_css_class' => 'col-date',
                'sortable' => true,
                'filter' => true
            ]
        );
        $this->addColumn(
            'created_at',
            [
                'header' => __('Created At'),
                'index' => 'created_at',
                'type' => 'datetime',
                'header_css_class' => 'col-name col-date-min-width',
                'column_css_class' => 'col-name',
                'sortable' => false,
//                'renderer' => \Branch8\Customer\Block\Adminhtml\Customer\Edit\Tab\Renderer\Detail::class,
                'filter' => false
            ]
        );
        return parent::_prepareColumns();
    }
    /**
     * Get row url
     *
     * @param \Magento\Review\Model\Review|\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return false;
    }
    /**
     * Determine ajax url for grid refresh
     *
     * @return string
     */
    public function getGridUrl()
    {
        return '#';
    }

}