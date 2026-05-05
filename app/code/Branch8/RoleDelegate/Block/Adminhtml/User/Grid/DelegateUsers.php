<?php

namespace Branch8\RoleDelegate\Block\Adminhtml\User\Grid;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Registry;
use Branch8\RoleDelegate\Model\ResourceModel\Delegate\CollectionFactory;
use Branch8\RoleDelegate\Model\Source\AdminUsers as AdminUsersSource;
use Branch8\MarketplaceProduct\Model\Source\AdminRoles as AdminRolesSource;
use Branch8\RoleDelegate\Model\Source\DelegateStatus as DelegateStatusSource;

class DelegateUsers extends Extended
{
    /**
     * @var Registry
     */
    private $coreRegistry = null;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var AdminUsersSource
     */
    protected $adminUsers;

    /**
     * @var AdminRolesSource
     */
    protected $adminRoles;

    /**
     * @var DelegateStatusSource
     */
    protected $delegateStatus;

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param Registry $coreRegistry
     * @param CollectionFactory $collectionFactory
     * @param AdminUsersSource $adminUsers
     * @param AdminRolesSource $adminRoles
     * @param DelegateStatusSource $delegateStatus
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        Registry $coreRegistry,
        CollectionFactory $collectionFactory,
        AdminUsersSource $adminUsers,
        AdminRolesSource $adminRoles,
        DelegateStatusSource $delegateStatus,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->collectionFactory = $collectionFactory;
        $this->adminUsers = $adminUsers;
        $this->adminRoles = $adminRoles;
        $this->delegateStatus = $delegateStatus;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     * @throws FileSystemException
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('delegateUsers_tab_grid');
        $this->setDefaultSort('id');
        $this->setUseAjax(true);
    }

    /**
     * @return Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            'user_id',
            [
                'type' => 'options',
                'header' => __('Original User'),
                'index' => 'user_id',
                'options' => $this->adminUsers->toArray()
            ]
        );
        $this->addColumn(
            'delegate_user_id',
            [
                'type' => 'options',
                'header' => __('Delegated User'),
                'index' => 'delegate_user_id',
                'options' => $this->adminUsers->toArray()
            ]
        );
        $this->addColumn(
            'role_id',
            [
                'type' => 'options',
                'header' => __('Delegated Role'),
                'index' => 'role_id',
                'options' => $this->adminRoles->toArray()
            ]
        );
        $this->addColumn(
            'start_at',
            [
                'header' => __('Start Date'),
                'index' => 'start_at',
                'type' => 'datetime',
                'align' => 'center',
                'default' => __('N/A'),
                'html_decorators' => ['nobr'],
                'header_css_class' => 'col-period',
                'column_css_class' => 'col-period'
            ]
        );

        $this->addColumn(
            'end_at',
            [
                'header' => __('End Date'),
                'index' => 'end_at',
                'type' => 'datetime',
                'align' => 'center',
                'default' => __('N/A'),
                'html_decorators' => ['nobr'],
                'header_css_class' => 'col-period',
                'column_css_class' => 'col-period'
            ]
        );
        $this->addColumn(
            'status',
            [
                'type' => 'options',
                'header' => __('Status'),
                'index' => 'status',
                'options' => $this->delegateStatus->toArray()
            ]
        );
        $this->addColumn(
            'created_by',
            [
                'type' => 'options',
                'header' => __('Created by'),
                'index' => 'created_by',
                'options' => $this->adminUsers->toArray()
            ]
        );
        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'type' => 'action',
                'getter' => 'getId',
                'actions' => [
                    [
                        'caption' => __('Edit'),
                        'url' => [
                            'path' => 'adminhtml/user/edit',
                            'params' => [
                                'user_id' => $this->getRequest()->getParam('user_id')
                            ]
                        ],
                        'field' => 'delegate_id'
                    ],
                    [
                        'caption' => __('Cancel'),
                        'url' => [
                            'path' => 'roledelegate/manage/cancel'
                        ],
                        'field' => 'delegate_id',
                        'confirm' => __('Are you sure to cancel selected items?')
                    ],
                ],
                'filter' => false,
                'sortable' => false,
                'renderer' => 'Branch8\\RoleDelegate\\Block\\Adminhtml\\User\\Grid\\Action',
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var \Branch8\RoleDelegate\Model\ResourceModel\Delegate\Collection $collection */
        $collection = $this->collectionFactory->create();
        if ($this->getRequest()->getParam('user_id')) {
            $collection->addFieldToFilter('user_id', ['eq' => $this->getRequest()->getParam('user_id')]);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('roledelegate/manage/grid', ['_current' => true]);
    }
}
