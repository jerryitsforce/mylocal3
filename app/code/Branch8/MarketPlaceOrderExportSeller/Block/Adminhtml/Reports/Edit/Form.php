<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportSeller\Block\Adminhtml\Reports\Edit;

use Magento\Framework\Stdlib\DateTime\DateTime;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory
     */
    private $sellerCollectionFactory;
    /**
     * @var DateTime
     */
    private $date;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Webkul\MpMassUpload\Helper\Data $massUploadHelper
     * @param DateTime $date
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context                          $context,
        \Magento\Framework\Registry                                      $registry,
        \Magento\Framework\Data\FormFactory                              $formFactory,
        \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerCollectionFactory,
        DateTime                                                         $date,
        array                                                            $data = []
    )
    {
        $this->sellerCollectionFactory = $sellerCollectionFactory;
        $this->date = $date;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init form.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('run_form');
    }

    /**
     * Prepare form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $sellerList = $this->getSellerList();
        $form = $this->_formFactory->create(
            ['data' =>
                [
                    'id' => 'edit_form',
                    'enctype' => 'multipart/form-data',
                    'action' => $this->getParentBlock()->getData('action'),
                    'method' => 'post'
                ]
            ]
        );
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['class' => 'fieldset-wide']
        );
        $fieldset->addField(
            'from_date',
            'date',
            [
                'label' => __('From Date'),
                'name' => 'from_date',
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'style' => 'width: 40%',
                'format' => 'y-MM-dd',
                'required' => 'true',
                'date_format' => 'y-MM-dd',
                'value' => null
            ]
        );
        $fieldset->addField(
            'to_date',
            'date',
            [
                'label' => __('To Date'),
                'name' => 'to_date',
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'style' => 'width: 40%',
                'format' => 'y-MM-dd',
                'required' => 'true',
                'date_format' => 'y-MM-dd',
                'value' => null
            ]
        );
        $fieldset->addField(
            'seller_code',
            'select',
            [
                'label' => __('Select Seller'),
                'title' => __('Select Seller'),
                'name' => 'seller_id',
                'required' => false,
                'options' => $sellerList,
            ]
        );
        $fieldset->addField(
            'submit',
            'submit',
            [
                'label' => __('Submit'),
                'title' => __('Submit'),
                'name' => 'submit',
                'value' => __('Submit'),
            ]
        );
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * @return array
     */
    private function getSellerList()
    {
        $sellerList = ['' => __('All')];
        $collection = $this->sellerCollectionFactory
            ->create()->addFieldToFilter('is_seller', 1);

        $select = $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(['entity_id', 'seller_code', 'shop_title'])
            ->where('seller_code IS NOT NULL');
        foreach ($collection as $item) {
            $sellerList[$item->getData('seller_code')] =$item->getData('shop_title'). ' (' . $item->getSellerCode() . ')';
        }
        return $sellerList;
    }
}
