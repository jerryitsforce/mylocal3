<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab\Grid;

class ContractFiles extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Branch8\SellerContactInformation\Model\ResourceModel\ContractFiles\CollectionFactory
     */
    protected $contractFilesCollectionFactory;

    /**
     * @var \Branch8\SellerContactInformation\Model\ContractFilesFactory
     */
    protected $contractFilesFactory;

    protected $yesno;
    
    protected $statusSource;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Branch8\SellerContactInformation\Model\ResourceModel\ContractFiles\CollectionFactory $contractFilesCollectionFactory
     * @param \Branch8\SellerContactInformation\Model\ContractFilesFactory $contractFilesFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Branch8\SellerContactInformation\Model\ResourceModel\ContractFiles\CollectionFactory $contractFilesCollectionFactory,
        \Branch8\SellerContactInformation\Model\ContractFilesFactory $contractFilesFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Config\Model\Config\Source\Yesno $yesno,
        \Branch8\SellerContactInformation\Model\Config\Source\ContractStatus $statusSource,
        array $data = []
    ) {
        $this->contractFilesCollectionFactory = $contractFilesCollectionFactory;
        $this->contractFilesFactory = $contractFilesFactory;
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $backendHelper, $data);
        $this->yesno = $yesno;
        $this->statusSource = $statusSource;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('contractsFiles_tab_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
    }

    protected function _prepareFilterButtons()
    {
        
        $this->setChild(
            'add_btn',
            $this->getLayout()->createBlock(
                \Magento\Backend\Block\Widget\Button::class,
                'add_btn'
            )->setData(
                [
                    'label' => __('Add Contract'),
                    'onclick' => 'window.open("'.$this->getUrl('marketplace/contractFile/edit', ['seller_id' => $this->getRequest()->getParam('id'), 'cid' => 0]).'", "_self");',
                    'class' => 'action-secondary',
                ]
            )->setDataAttribute(['action' => 'grid-filter-apply'])
        );
        $this->setChild(
            'history_button',
            $this->getLayout()->createBlock(
                \Magento\Backend\Block\Widget\Button::class,
                'history_button'
            )->setData(
                [
                    'label' => __('Contract history'),
                    'onclick' => 'window.open("'.$this->getUrl('marketplace/contractFile/history', ['seller_id' => $this->getRequest()->getParam('id')]).'", "_blank");',
                    'class' => 'action-secondary',
                ]
            )->setDataAttribute(['action' => 'grid-filter-apply'])
        );
        parent::_prepareFilterButtons();
    }

    /**
     * @return Grid
     */
    protected function _prepareCollection()
    {
        $sellerId = $this->getRequest()->getParam('id');
        $collection = $this->contractFilesCollectionFactory->create();
        $collection->addFieldToFilter('seller_id', ['eq' => $sellerId]);
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            'contracts_from',
            [
                'header' => __('Contracts From'),
                'index' => 'contracts_from',
                'type' => 'datetime'
            ]
        );
        $this->addColumn(
            'contracts_to',
            [
                'header' => __('Contracts To'),
                'index' => 'contracts_to',
                'type' => 'datetime'
            ]
        );
        $this->addColumn(
            'commission',
            [
                'header' => __('Total Commission %'),
                'index' => 'commission'
            ]
        );
        $this->addColumn(
            'min_commission',
            [
                'header' => __('Verification %'),
                'index' => 'min_commission'
            ]
        );
        // $this->addColumn(
        //     'seller_id',
        //     [
        //         'header' => __('Seller ID'),
        //         'index' => 'seller_id'
        //     ]
        // );
        $this->addColumn(
            'file_name',
            [
                'header' => __('File Name'),
                'index' => 'file_name',
                'renderer' => \Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab\Renderer\Filename::class
            ]
        );
        
        // $this->addColumn(
        //     'applicable_period',
        //     [
        //         'header' => __('Applicable Period'),
        //         'index' => 'applicable_period'
        //     ]
        // );
        
        $this->addColumn(
            'update_existed_product',
            [
                'type' => 'options',
                'index' => 'update_existed_product',
                'header' => __('Update Existed Items'),
                'required' => true,
                'options' => $this->yesno->toArray()
            ]
        );
        
        // $this->addColumn(
        //     'updated_at',
        //     [
        //         'header' => __('Upload time'),
        //         'index' => 'updated_at',
        //         'type' => 'datetime'
        //     ]
        // );
        // $this->addColumn(
        //     'updated_by',
        //     [
        //         'header' => __('Updated by'),
        //         'index' => 'updated_by'
        //     ]
        // );
        $this->addColumn(
            'is_active',
            [
                'header' => __('Status'),
                'type' => 'options',
                'index' => 'is_active',
                // 'renderer' => \Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab\Renderer\Status::class,
                'options' => $this->statusSource->toArray()
            ]
        );
        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'type' => 'action',
                'getter' => 'getEntityId',
                'filter' => false,
                'sortable' => false,
                'renderer' => 'Branch8\\SellerContactInformation\\Block\\Adminhtml\\Customer\\Edit\\Tab\\Renderer\\Action',
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('marketplace/contractFile/grid', ['_current' => true]);
    }

    public function getMainButtonsHtml()
    {
        $html = '';
        $html .= $this->getAddButtonHtml();
        $html .= $this->getHistoryButtonHtml();
        if ($this->getFilterVisibility()) {
            $html .= $this->getSearchButtonHtml();
            $html .= $this->getResetFilterButtonHtml();
            
        }
        return $html;
    }

    protected function getAddButtonHtml(){
        return $this->getChildHtml('add_btn');
    }
    protected function getHistoryButtonHtml(){
        return $this->getChildHtml('history_button');
    }

}
