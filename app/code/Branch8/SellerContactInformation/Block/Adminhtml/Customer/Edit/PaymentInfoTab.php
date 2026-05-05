<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;

/**
 * Customer account form block.
 */
class PaymentInfoTab extends Generic implements TabInterface
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
     * Construct
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_systemStore = $systemStore;
        $this->customerEdit = $customerEdit;
        parent::__construct($context, $registry, $formFactory, $data);
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
        return __('Invoice And Bank Information');
    }

    /**
     * Get tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Invoice And Bank Information');
    }

    /**
     * Get seller status
     *
     * @return bool
     */
    protected function getSellerStatus()
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
     * Can show tab
     *
     * @return bool
     */
    public function canShowTab()
    {
        return $this->getSellerStatus();
    }

    /**
     * Get is isHidde
     *
     * @return bool
     */
    public function isHidden()
    {
        return $this->getSellerStatus();
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
            ['legend' => '']
        );
        $partner = $this->customerEdit->getSellerInfoCollection();
        $sellerId = $this->getCustomerId();
        $afterHtml = '';
        if ($partner['basic_information_registration_file']) {
            $fileName = $partner['basic_information_registration_file'];
            $fileUrl = $mediaUrl.'marketplace/remittance_file/'.$sellerId.'/'.$fileName;
            $ext = pathinfo($partner['basic_information_registration_file'], PATHINFO_EXTENSION);
            if( $ext && strtolower($ext) == 'pdf') {
                $afterHtml = '</br></br><a style="margin-right:20px" href="' . $fileUrl . '" download="' . $fileName . '">' . __('Download Uploaded File [PDF]') . '</a>';
            } else {
                $afterHtml = '<img style="margin:5px 0;width:700px;"
                src="'.$fileUrl.'"/>';
            }

        }
        $fieldset->addField(
            'basic_information_registration_file',
            'file',
            [
                'name' => 'basic_information_registration_file',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Seller Remittance Basic Information Registration File'),
                'title' => __('Seller Remittance Basic Information Registration File'),
                'value' => '',
                'after_element_html' => '<label style="width:100%;">
                    Allowed File Type : [ jpg, jpeg, png, pdf ]
                </label>' . $afterHtml,
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
        $html = $this->getLayout()->createBlock(
            \Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab\PaymentInfo::class
        )->toHtml();
        $html .= parent::getFormHtml();

        return $html;
    }
}
