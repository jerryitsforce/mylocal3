<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit;

use Magento\Directory\Model\ResourceModel\Country\Collection as CountryModel;

/**
 * Customer account form block.
 */
class CommissionTab extends \Webkul\Marketplace\Block\Adminhtml\Customer\Edit\CommissionTab
{

    // public function __construct(
    //     \Magento\Backend\Block\Template\Context $context,
    //     \Magento\Framework\Registry $registry,
    //     \Magento\Framework\Data\FormFactory $formFactory,
    //     \Magento\Store\Model\System\Store $systemStore,
    //     CountryModel $country,
    //     \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
    //     array $data = []
    // ){
    //     parent::__construct($context, $registry, $formFactory, $systemStore, $country, $customerEdit, $data);
    // }

    /**
     * Get tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Commission Settings And Contract');
    }

    /**
     * Get tab title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Commission Settings And Contract');
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
        // $form->setHtmlIdPrefix('custom_');
        // $form->setFieldNameSuffix('custom');
        $form->setData('data-mage-init', ['validation' => []]);
        $form->setData('id' ,'contractForm');
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Commission Settings And Contract')]
        );

        $commissionRate = '';
        $minCommissionRate = '';
        $specialCommissionRate = '';
        $patnerInfo = $this->customerEdit->getSalesPartnerCollection()->getFirstItem();
        if($patnerInfo){
            $commissionRate = $patnerInfo['default_commission_rate'];
            $minCommissionRate = $patnerInfo['default_min_commission_rate'];
            $specialCommissionRate = $patnerInfo['special_commission_rate'];
        }

        $fieldset->addField(
            'default_commisssion_rate',
            'text',
            [
                'name' => 'default_commisssion_rate',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Default Total Commission %'),
                'title' => __('Default Total Commission %'),
                'class' => 'validate-number validate-digits-range digits-range-0-100',
                'required' => true,
                'value' => $commissionRate
            ]
        );
        $fieldset->addField(
            'default_min_commisssion_rate',
            'text',
            [
                'name' => 'default_min_commisssion_rate',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Default Verification %'),
                'title' => __('Default Verification %'),
                'class' => 'validate-number validate-digits-range digits-range-0-100',
                'required' => true,
                'value' => $minCommissionRate
            ]
        );

        $fieldset->addField(
            'enable_special_commission',
            'checkbox',
            [
                'name' => 'enable_special_commission',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Enable Special Commission'),
                'title' => __('Enable Special Commission'),
                'value' => 1,
                'checked' => !empty($specialCommissionRate),
                'disabled' => false,
                'class' => 'admin__control-checkbox',
                'onchange' => 'document.getElementById(\'marketplace_special_commission\').disabled = !this.checked; this.value = this.checked ? 1 : 0;', // Ensure value is 1 or 0 on change
                'note' => __('對賬專用，沒用特別抽成請忽略')
            ]
        );
        $fieldset->addField(
            'special_commission',
            'text',
            [
                'name' => 'special_commission',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Special Commission %'),
                'title' => __('Special Commission %'),
                'class' => 'validate-number',
                'required' => false,
                'value' => $specialCommissionRate,
                'disabled' => empty($specialCommissionRate) // 如果 specialCommissionRate 為空，則禁用
            ]
        );

        $fieldset->addField(
            'update_default_commission',
            'button',
            [
                'name' => 'update_default_commission',
                'data-form-part' => $this->getData('target_form'),
                'label' => '',
                'title' => '',
                'value' => __('Save Default Settings'),
                "class" => "action-default scalable action-secondary",
                'data-action' => $this->getUrl('marketplace/contractFile/saveDefaultCommission', ['seller_id' => $this->getRequest()->getparam('id')])
            ]
        );
        
        
        $form->setUseContainer(true);
        $this->setForm($form);

        return $this;
    }
}
