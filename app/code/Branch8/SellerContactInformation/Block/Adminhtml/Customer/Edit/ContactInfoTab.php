<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Customer\Model\CustomerFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Model\SellerFactory;

/**
 * Customer Seller form block.
 */
class ContactInfoTab extends Generic implements TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     *
     * @var string|null
     */
    protected $_dob = null;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    protected $_country;

    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /**
     * @var CustomerFactory
     */
    protected $customerModel;

    /**
     * @var \Magento\Store\Ui\Component\Listing\Column\Store\Options
     */
    protected $options;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @var SellerFactory
     */
    protected $sellerModel;

    /**
     * Construct
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Directory\Model\ResourceModel\Country\Collection $country
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit
     * @param CustomerFactory $customerModel
     * @param \Magento\Store\Ui\Component\Listing\Column\Store\Options $options
     * @param MpHelper $mpHelper
     * @param SellerFactory $sellerModel
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Directory\Model\ResourceModel\Country\Collection $country,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        CustomerFactory $customerModel,
        \Magento\Store\Ui\Component\Listing\Column\Store\Options $options,
        MpHelper $mpHelper,
        SellerFactory $sellerModel,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_systemStore = $systemStore;
        $this->_country = $country;
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->customerEdit = $customerEdit;
        $this->customerModel = $customerModel;
        $this->options = $options;
        $this->mpHelper = $mpHelper;
        $this->sellerModel = $sellerModel;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Get wysiwg config
     *
     * @return void
     */
    public function getWysiwygConfig()
    {
        $config = $this->_wysiwygConfig->getConfig();
        $config = json_encode($config->getData());
    }

    /**
     * Get customer id
     *
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(
            RegistryConstants::CURRENT_CUSTOMER_ID
        );
    }

    /**
     * Get tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Contact Information');
    }

    /**
     * Get tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Contact Information');
    }

    /**
     * Can show tab
     *
     * @return bool
     */
    public function canShowTab()
    {
        $coll = $this->customerEdit->getMarketplaceUserCollection();
        $isSeller = false;
        foreach ($coll as $row) {
            $isSeller = $row->getIsSeller();
        }
        if ($this->getCustomerId() && $isSeller) {
            return true;
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
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Contact Information')]
        );
        $partner = $this->customerEdit->getSellerInfoCollection();
        
        $fieldset->addField(
            'contact_info_change_settings_enable',
            'checkbox',
            [
                'name' => 'contact_info_change_settings_enable',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Change Settings'),
                'title' => __('Change Settings'),
                'onchange' => 'this.value = this.checked;',
                'after_element_html' => "<script>
                require([
                    'jquery'
                ], function($){
                    $('#marketplace_contact_info_change_settings_enable').on('change', function () {
                        if (this.checked === true) {
                            $('#marketplace_primary_contact').removeAttr('disabled');
                            $('#marketplace_contact_number').removeAttr('disabled');
                            $('#marketplace_primary_contact_email').removeAttr('disabled');
                            $('#marketplace_financial_liaison').removeAttr('disabled');
                            $('#marketplace_financial_liaison_contact_number').removeAttr('disabled');
                            $('#marketplace_financial_liaison_email').removeAttr('disabled');
                            $('#marketplace_customer_service_contact').removeAttr('disabled');
                            $('#marketplace_customer_service_contact_number').removeAttr('disabled');
                            $('#marketplace_customer_service_contact_email').removeAttr('disabled');
                            $('#marketplace_logistics_and_delivery_contract').removeAttr('disabled');
                            $('#marketplace_logistics_and_delivery_phone_number').removeAttr('disabled');
                            $('#marketplace_logistics_and_delivery_email').removeAttr('disabled');
                        } else {
                            $('#marketplace_primary_contact').attr('disabled', 'disabled');
                            $('#marketplace_contact_number').attr('disabled', 'disabled');
                            $('#marketplace_primary_contact_email').attr('disabled', 'disabled');
                            $('#marketplace_financial_liaison').attr('disabled', 'disabled');
                            $('#marketplace_financial_liaison_contact_number').attr('disabled', 'disabled');
                            $('#marketplace_financial_liaison_email').attr('disabled', 'disabled');
                            $('#marketplace_customer_service_contact').attr('disabled', 'disabled');
                            $('#marketplace_customer_service_contact_number').attr('disabled', 'disabled');
                            $('#marketplace_customer_service_contact_email').attr('disabled', 'disabled');
                            $('#marketplace_logistics_and_delivery_contract').attr('disabled', 'disabled');
                            $('#marketplace_logistics_and_delivery_phone_number').attr('disabled', 'disabled');
                            $('#marketplace_logistics_and_delivery_email').attr('disabled', 'disabled');
                        }
                    });
                });
                </script>"
            ]
        );
        $fieldset->addField(
            'primary_contact',
            'text',
            [
                'name' => 'primary_contact',
                'data-form-part' => $this->getData('target_form'),
                'label' => __("Main Contact"),
                'title' => __("Main Contact"),
                'value' => $partner['primary_contact'],
                'disabled' => 'disabled',
                'required' => true
            ]
        );
        $fieldset->addField(
            'contact_number',//default extension field
            'text',
            [
                'name' => 'contact_number',
                'data-form-part' => $this->getData('target_form'),
                'label' => __("Main Contact's Phone Number"),
                'title' => __("Main Contact's Phone Number"),
                'value' => $partner['contact_number'],
                'disabled' => 'disabled',
                'required' => true
            ]
        );
        $fieldset->addField(
            'primary_contact_email',
            'text',
            [
                'name' => 'primary_contact_email',
                'data-form-part' => $this->getData('target_form'),
                'label' => __("Main Contact's Email"),
                'title' => __("Main Contact's Email"),
                'value' => $partner['primary_contact_email'],
                'disabled' => 'disabled',
                'required' => true,
                'class' => 'validate-email'
            ]
        );
        $fieldset->addField(
            'financial_liaison',
            'text',
            [
                'name' => 'financial_liaison',
                'data-form-part' => $this->getData('target_form'),
                'label' => __("Finance Contact"),
                'title' => __("Finance Contact"),
                'value' => $partner['financial_liaison'],
                'disabled' => 'disabled',
                'required' => true
            ]
        );
        $fieldset->addField(
            'financial_liaison_contact_number',
            'text',
            [
                'name' => 'financial_liaison_contact_number',
                'data-form-part' => $this->getData('target_form'),
                'label' => __("Finance Contact's Phone Number"),
                'title' => __("Finance Contact's Phone Number"),
                'value' => $partner['financial_liaison_contact_number'],
                'disabled' => 'disabled',
                'required' => true
            ]
        );
        $fieldset->addField(
            'financial_liaison_email',
            'text',
            [
                'name' => 'financial_liaison_email',
                'data-form-part' => $this->getData('target_form'),
                'label' => __("Finance Contact's Email"),
                'title' => __("Finance Contact's Email"),
                'value' => $partner['financial_liaison_email'],
                'disabled' => 'disabled',
                'required' => true,
                'class' => 'validate-email'
            ]
        );
        $fieldset->addField(
            'customer_service_contact',
            'text',
            [
                'name' => 'customer_service_contact',
                'data-form-part' => $this->getData('target_form'),
                'label' => __("Customer Service Contact"),
                'title' => __("Customer Service Contact"),
                'value' => $partner['customer_service_contact'],
                'disabled' => 'disabled',
                'required' => true
            ]
        );
        $fieldset->addField(
            'customer_service_contact_number',
            'text',
            [
                'name' => 'customer_service_contact_number',
                'data-form-part' => $this->getData('target_form'),
                'label' => __("Customer Service Contact's Phone Number"),
                'title' => __("Customer Service Contact's Phone Number"),
                'value' => $partner['customer_service_contact_number'],
                'disabled' => 'disabled',
                'required' => true
            ]
        );
        $fieldset->addField(
            'customer_service_contact_email',
            'text',
            [
                'name' => 'customer_service_contact_email',
                'data-form-part' => $this->getData('target_form'),
                'label' => __("Customer Service Contact's Email"),
                'title' => __("Customer Service Contact's Email"),
                'value' => $partner['customer_service_contact_email'],
                'disabled' => 'disabled',
                'required' => true,
                'class' => 'validate-email'
            ]
        );
        $fieldset->addField(
            'logistics_and_delivery_contract',
            'text',
            [
                'name' => 'logistics_and_delivery_contract',
                'data-form-part' => $this->getData('target_form'),
                'label' => __("Logistics And Delivery Contact"),
                'title' => __("Logistics And Delivery Contact"),
                'value' => $partner['logistics_and_delivery_contract'],
                'disabled' => 'disabled',
                'required' => true
            ]
        );
        $fieldset->addField(
            'logistics_and_delivery_phone_number',
            'text',
            [
                'name' => 'logistics_and_delivery_phone_number',
                'data-form-part' => $this->getData('target_form'),
                'label' => __("Logistics And Delivery Phone Number"),
                'title' => __("Logistics And Delivery Phone Number"),
                'value' => $partner['logistics_and_delivery_phone_number'],
                'disabled' => 'disabled',
                'required' => true
            ]
        );
        $fieldset->addField(
            'logistics_and_delivery_email',
            'text',
            [
                'name' => 'logistics_and_delivery_email',
                'data-form-part' => $this->getData('target_form'),
                'label' => __("Logistics And Delivery Email"),
                'title' => __("Logistics And Delivery Email"),
                'value' => $partner['logistics_and_delivery_email'],
                'disabled' => 'disabled',
                'required' => true,
                'class' => 'validate-email'
            ]
        );
       
        $form->setUseContainer(true);

        $this->setForm($form);

        return parent::_prepareForm();
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
}
