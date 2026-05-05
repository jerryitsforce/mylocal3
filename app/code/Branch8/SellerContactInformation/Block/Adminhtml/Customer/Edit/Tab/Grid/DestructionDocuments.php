<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab\Grid;

class DestructionDocuments extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Branch8\SellerDocument\Model\ResourceModel\File\CollectionFactory
     */
    protected $destructionDocumentsCollectionFactory;

    /**
     * @var \Branch8\SellerDocument\Model\FileFactory
     */
    protected $destructionDocumentsFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Branch8\SellerDocument\Model\ResourceModel\File\CollectionFactory  $destructionDocumentsCollectionFactory
     * @param \Branch8\SellerDocument\Model\FileFactory $destructionDocumentsFactory,
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Branch8\SellerDocument\Model\ResourceModel\File\CollectionFactory $destructionDocumentsCollectionFactory,
        \Branch8\SellerDocument\Model\FileFactory $destructionDocumentsFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->destructionDocumentsCollectionFactory = $destructionDocumentsCollectionFactory;
        $this->destructionDocumentFactory = $destructionDocumentsFactory;
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('destructionDocuments_tab_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
    }

    /**
     * @return Grid
     */
    protected function _prepareCollection()
    {
        $sellerId = $this->getRequest()->getParam('id');
        $collection = $this->destructionDocumentsCollectionFactory->create();
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
            'seller_id',
            [
                'header' => __('Seller ID'),
                'index' => 'seller_id'
            ]
        );
        $this->addColumn(
            'personal_data_destruction_affidavit',
            [
                'header' => __('Personal data destruction affidavit'),
                'index' => 'personal_data_destruction_affidavit'
            ]
        );
        $this->addColumn(
            'personal_data_destruction_certificate',
            [
                'header' => __('Personal data destruction certificate'),
                'index' => 'personal_data_destruction_certificate'
            ]
        );
        $this->addColumn(
            'personal_data_destruction_period',
            [
                'header' => __('Personal Data Destruction Period'),
                'index' => 'personal_data_destruction_period'
            ]
        );
        $this->addColumn(
            'uploaded_at',
            [
                'header' => __('Updated Time'),
                'index' => 'uploaded_at',
                'type' => 'datetime'
            ]
        );
        $this->addColumn(
            'account_make_change',
            [
                'header' => __('Change made account name'),
                'index' => 'account_make_change'
            ]
        );
        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'type' => 'action',
                'getter' => 'getFileId',
                'actions' => [
                    [
                        'caption' => __('Download Affidavit'),
                        'url' => [
                            'base' => 'marketplace/destructionDocuments/downloadAffidavit',
                            'params' => [
                                'seller_id' => $this->getRequest()->getParam('id')
                            ]
                        ],
                        'field' => 'file_id'
                    ],
                    [
                        'caption' => __('Download certificate'),
                        'url' => [
                            'base' => 'marketplace/destructionDocuments/downloadCertificate',
                            'params' => [
                                'seller_id' => $this->getRequest()->getParam('id')
                            ]
                        ],
                        'field' => 'file_id'
                    ]
                ],
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
        return $this->getUrl('marketplace/destructionDocuments/grid', ['_current' => true]);
    }

}