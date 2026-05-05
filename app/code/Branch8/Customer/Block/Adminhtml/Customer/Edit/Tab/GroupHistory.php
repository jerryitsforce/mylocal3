<?php

namespace Branch8\Customer\Block\Adminhtml\Customer\Edit\Tab;

class GroupHistory extends \Magento\Backend\Block\Widget\Grid\Extended{
    /**
     * @var \Branch8\Customer\Model\ResourceModel\LevelHistory\CollectionFactory
     */
    protected $groupHistoryFactory;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory 
     */
    protected $groupCollectionFactory;

    /**
     * @param \Branch8\Customer\Model\ResourceModel\LevelHistory\CollectionFactory $groupHistoryCollectionFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Branch8\Customer\Model\ResourceModel\LevelHistory\CollectionFactory $groupHistoryCollectionFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory,
        array $data = []
    ){
        parent::__construct($context, $backendHelper, $data);
        $this->groupHistoryFactory = $groupHistoryCollectionFactory;
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    /**
     * Initialize grid
     *
     * @return void
     */
    protected function _construct(){
        parent::_construct();
        $this->setId('groupHistoryGrid');
        $this->setDefaultSort('created_at');
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Review\Block\Adminhtml\Grid
     */
    protected function _prepareCollection(){
        /** @var $collection \Branch8\Customer\Model\ResourceModel\GroupHistory\Collection */
        $collection = $this->groupHistoryFactory->create();
        if ($this->getCustomerId() || $this->getRequest()->getParam('customerId', false)) {
            $customerId = $this->getCustomerId();
            if (!$customerId) {
                $customerId = $this->getRequest()->getParam('customerId');
            }
            $this->setCustomerId($customerId);
            $collection->addFieldToFilter('customer_id', $customerId);
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    /**
     * Prepare grid columns
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareColumns(){
        $allGroups = $this->groupCollectionFactory->create()
            ->addFieldToSelect('customer_group_id')
            ->addFieldToSelect('fullname');
        $groupOptions = [];
        foreach ($allGroups as $group) {
            $groupOptions[$group->getId()] = $group->getFullname();
        }
        $this->addColumn(
            'current_level',
            [
                'header' => __('Old level'),
                'index' => 'current_level',
                'type' => 'options',
                'truncate' => 50,
                'escape' => true,
                'sortable' => false,
                'options' => $groupOptions,
                'filter' => false
            ]
        );
        $this->addColumn(
            'new_level',
            [
                'header' => __('New Level'),
                'index' => 'new_level',
                'type' => 'options',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name',
                'sortable' => false,
                'options' => $groupOptions,
                'filter' => false
            ]
        );
        $this->addColumn(
            'created_at',
            [
                'header' => __('Created'),
                'type' => 'datetime',
                'index' => 'created_at',
                'header_css_class' => 'col-date col-date-min-width',
                'column_css_class' => 'col-date',
                'sortable' => false,
                'filter' => false
            ]
        );
        $this->addColumn(
            'details',
            [
                'header' => __('Details'),
                'index' => 'details',
                'type' => 'text',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name',
                'sortable' => false,
                'renderer' => \Branch8\Customer\Block\Adminhtml\Customer\Edit\Tab\Renderer\Detail::class,
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