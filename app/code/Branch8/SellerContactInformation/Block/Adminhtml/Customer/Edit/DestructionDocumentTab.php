<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit;;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Branch8\SellerDocument\Helper\Data as SellerDocumentHelper;

/**
 * Customer account form block.
 */
class DestructionDocumentTab extends Generic implements TabInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    protected $sellerDocumentHelper;

    /**
     * Construct
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        SellerDocumentHelper $sellerDocumentHelper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->customerEdit = $customerEdit;
        $this->sellerDocumentHelper = $sellerDocumentHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Get customer id
     *
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * Get tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Personal data destruction record');
    }

    /**
     * Get tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Personal data destruction record');
    }

    /**
     * Can show tab
     *
     * @return bool
     */
    public function canShowTab()
    {
        $canShowTab = $this->sellerDocumentHelper->showDocumentTab();

        if ($canShowTab) {
            $coll = $this->customerEdit->getMarketplaceUserCollection();
            $isSeller = false;
            foreach ($coll as $row) {
                $isSeller = $row->getIsSeller();
            }
            if ($this->getCustomerId() && $isSeller) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get is hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        $coll = $this->customerEdit->getMarketplaceUserCollection();
        $isSeller = false;
        foreach ($coll as $row) {
            $isSeller = $row->getIsSeller();
        }
        if ($this->getCustomerId() && $isSeller) {
            return false;
        }

        return true;
    }

    /**
     * Tab class getter.
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * Return URL link to Tab content.
     *
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call.
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * Init form
     *
     * @return void
     */
    public function initForm()
    {
        if (!$this->canShowTab()) {
            return $this;
        }
        /**@var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('marketplace_');
        $mediaUrl = $this->_storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Personal data destruction record')]
        );
        $partner = $this->customerEdit->getSellerInfoCollection();

        $fieldset->addField(
            'most_recent_personal_data_destruction_period',
            'text',
            [
                'name' => 'most_recent_personal_data_destruction_period',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Most Recenrt Personal Data Destruction Period'),
                'title' => __('Most Recenrt Personal Data Destruction Period'),
                'disabled' => 'disabled',
                'value' => $partner['most_recent_personal_data_destruction_period'],
            ]
        );

        $status = $partner['destruction_document_status'] ? __("Enabled") : __("Disabled");
        $fieldset->addField(
            'destruction_document_status',
            'text',
            [
                'name' => 'destruction_document_status',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Destruction Document Status'),
                'title' => __('Destruction Document Status'),
                'disabled' => 'disabled',
                'value' => $status,
            ]
        );

        $options = [
            ['value' => '90', 'label' => '3 months ( 90 days )']
        ];
        $fieldset->addField(
            'personal_data_destruction_period',
            'select',
            [
                'name' => 'personal_data_destruction_period',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Choose personal data destruction period'),
                'title' => __('Choose personal data destruction period'),
                'values' => $options
            ]
        );

        $documentTemplateFileUrl = $this->sellerDocumentHelper->getDocumentTemplateFileUrl();
        $after_element_html = '';
        if ($documentTemplateFileUrl) {
            $documentTemplateFileName = basename($documentTemplateFileUrl);
            $after_element_html = '<a style="margin-right:20px" href="' . $documentTemplateFileUrl . '" download="' . $documentTemplateFileName . '">' . __('Download Affidavit') . '</a>';
        }
        $after_element_html = $after_element_html . '<label style="width:100%;">Allowed : [doc, docx, pdf]</label>';
        $sellerId = $this->getCustomerId();
        $fileName = $partner['personal_data_destruction_affidavit'];
        if (!empty($fileName)) {
            $filePath = 'marketplace/seller_destruction_documents/'.$sellerId.'/'.$fileName;
        }
        $fieldset->addField(
            'personal_data_destruction_affidavit',
            'file',
            [
                'name' => 'personal_data_destruction_affidavit',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Personal data destruction affidavit'),
                'title' => __('Personal data destruction affidavit'),
                'value' => '',
                'after_element_html' => $after_element_html,
            ]
        );


        $after_element_html2 = '<label style="width:100%;">Allowed Images File Type : [png, jpg, jpeg]</label>';
        $fileName = $partner['personal_data_destruction_certificate'];
        if (!empty($fileName)) {
            $filePath = 'marketplace/seller_destruction_documents/'.$sellerId.'/'.$fileName;
        }
        $fieldset->addField(
            'personal_data_destruction_certificate',
            'file',
            [
                'name' => 'personal_data_destruction_certificate',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Personal data destruction certificate'),
                'title' => __('Personal data destruction certificate'),
                'value' => '',
                'after_element_html' => $after_element_html2,
            ]
        );

        $this->setForm($form);

        return $this;
    }

    /**
     * To html
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->canShowTab()) {
            $this->initForm();

            return parent::_toHtml();
        } else {
            return '';
        }
    }

    /**
     * Prepare the layout.
     *
     * @return $this
     */
    public function getFormHtml()
    {
        $html = parent::getFormHtml();
        $html .= $this->getLayout()->createBlock(
            \Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab\DestructionDocument::class
        )->toHtml();

        return $html;
    }
}
