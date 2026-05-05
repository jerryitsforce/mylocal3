<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Branch8\SellerContactInformation\Helper\Data as SellerHelper;
use Webkul\Marketplace\Model\SellerFactory;

/**
 * Customer Seller form block.
 */
class SalespersonSettingsTab extends Generic implements TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var CustomerFactory
     */
    protected $customerModel;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @var SellerHelper
     */
    protected $sellerHelper;

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
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit
     * @param CustomerFactory $customerModel
     * @param CustomerRepositoryInterface $customerModel
     * @param MpHelper $mpHelper
     * @param SellerHelper $mpHelper
     * @param SellerFactory $sellerModel
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        CustomerFactory $customerModel,
        CustomerRepositoryInterface $customerRepository,
        MpHelper $mpHelper,
        SellerHelper $sellerHelper,
        SellerFactory $sellerModel,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->customerEdit = $customerEdit;
        $this->customerModel = $customerModel;
        $this->customerRepository = $customerRepository;
        $this->mpHelper = $mpHelper;
        $this->sellerHelper = $sellerHelper;
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
        return __('Salesperson Settings');
    }

    /**
     * Get tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Salesperson Settings');
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
        $customerId = $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Salesperson Settings')]
        );
        
        $partner = $this->customerEdit->getSellerInfoCollection();
        $options = $this->sellerHelper->getAdminRolesArrayOptions();
        
        $fieldset->addField(
            'salesperson_select',
            'multiselect',
            [
                'name' => 'salesperson_select',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Sales representative'),
                'title' => __('Sales representative'),
                'values' => $options,
                'value' => $partner['salesperson']
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
