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
class Tabs extends \Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Tabs
{

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
        $customerId = $this->_coreRegistry->registry(
            RegistryConstants::CURRENT_CUSTOMER_ID
        );
        $storeid = $this->_storeManager->getStore()->getId();
        $mediaUrl = $this->_storeManager->getStore()
                                        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Seller Account Information')]
        );
        $customer = $this->customerModel->create()->load($customerId);
        $partner = $this->customerEdit->getSellerInfoCollection();

        $allStoreViews = $this->options->toOptionArray();
        $len = count($allStoreViews);
        $allStoreViews[$len]['label'] = __('Admin Store');
        $allStoreViews[$len]['value'][0]['label'] = __('Admin Store View');
        $allStoreViews[$len]['value'][0]['value'] = 0;
        $allStores = $this->mpHelper->getAllStores();
        $currentUrl = $this->getCurrentUrl();
        $currentUrlArr = explode("store", $currentUrl);
        $currentUrlBase = $currentUrlArr[0];
        $storeUrl = $currentUrlBase."store/0";
        $data = '<input type="hidden" id="wk_mp_store0" value="'.$storeUrl.'">';
        foreach ($allStores as $store) {
            $storeUrl = $currentUrlBase."store/".$store->getId()."/";
            $data = $data.'<input type="hidden" id="wk_mp_store'.$store->getId().'" value="'.$storeUrl.'">';
        }
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $requestParams = $this->getRequest()->getParams();
        if (!isset($requestParams['store'])) {
            $collection = $this->sellerModel->create()->getCollection()
                          ->addFieldToFilter('seller_id', $customerId)
                          ->addFieldToFilter('store_id', $customer->getStoreId());
            if (count($collection)) {
                $storeId = $customer->getStoreId();
            }
        }

        $fieldset->addField(
            'seller_code',
            'text',
            [
                'name' => 'seller_code',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Seller Code'),
                'title' => __('Seller Code'),
                'value' => $partner['seller_code'],
                'required' => true
            ]
        );
        $fieldset->addField(
            'company_name',
            'text',
            [
                'name' => 'company_name',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Company Name'),
                'title' => __('Company Name'),
                'value' => $partner['company_name'],
                'required' => true
            ]
        );
        $fieldset->addField(
            'company_address',
            'text',
            [
                'name' => 'company_address',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Company Address'),
                'title' => __('Company Address'),
                'value' => $partner['company_address'],
                'required' => true
            ]
        );
        $fieldset->addField(
            'company_phone',
            'text',
            [
                'name' => 'company_phone',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Company Phone Number'),
                'title' => __('Company Phone Number'),
                'value' => $partner['company_phone'],
                'required' => true
            ]
        );
        $fieldset->addField(
            'company_representative',
            'text',
            [
                'name' => 'company_representative',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Company Representative'),
                'title' => __('Company Representative'),
                'value' => $partner['company_representative'],
                'required' => true
            ]
        );
        $fieldset->addField(
            'shop_title',
            'text',
            [
                'name' => 'shop_title',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Shop Title ( Store Name )'),
                'title' => __('Shop Title ( Store Name )'),
                'value' => $partner['shop_title'],
                'required' => true
            ]
        );

        $useCustomStatusField = false;//default extension have Seller Status (is_seller) field
        if ($useCustomStatusField) {
            $fieldset->addField(
                'status',//new field
                'select',
                [
                    'name' => 'status',
                    'data-form-part' => $this->getData('target_form'),
                    'label' => __('Status'),
                    'options' => [
                        '1' => __('Enable'),
                        '0' => __('Disable')
                    ],
                    'title' => __('Status'),
                    'value' => $partner['status'],
                    'required' => true
                ]
            );
        }

        /* Hide these field from default marketplace extension */
        // Tax/VAT Number
        // Select Country
        // Return Policy
        // Shipping Policy
        // Privacy Policy
        // Company Locality

        $fieldset->addField(
            'company_description',
            'editor',
            [
                'name' => 'company_description',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Company Description'),
                'title' => __('Company Description'),
                'value' => $partner['company_description'],
                'config'    => $this->_wysiwygConfig->getConfig([
                    'add_widgets' => false,
                    'add_variables' => false
                    ]),
                'wysiwyg'   => true,
                'required'  => true,
                'after_element_html' => ""
            ]
        );
        $fieldset->addField(
            'meta_keyword',
            'textarea',
            [
                'name' => 'meta_keyword',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Meta Keywords'),
                'title' => __('Meta Keywords'),
                'value' => $partner['meta_keyword'],
                'required'  => true
            ]
        );
        $fieldset->addField(
            'meta_description',
            'textarea',
            [
                'name' => 'meta_description',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Meta Description'),
                'title' => __('Meta Description'),
                'value' => $partner['meta_description'],
                'required'  => true
            ]
        );
        $fieldset->addField(
            'logo_pic',
            'file',
            [
                'name' => 'logo_pic',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Company Logo'),
                'title' => __('Company Logo'),
                'value' => $partner['logo_pic'],
                'after_element_html' => '<label style="width:100%;">
                    Allowed File Type : [jpg, jpeg, gif, png]
                </label>
                <img style="margin:5px 0;width:250px;"
                src="'.$mediaUrl.'avatar/'.$partner['logo_pic'].'"
                />'
            ]
        );
        $fieldset->addField(
            'banner_pic',
            'file',
            [
                'name' => 'banner_pic',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Company Banner'),
                'title' => __('Company Banner'),
                'value' => $partner['banner_pic'],
                'after_element_html' => '<label style="width:100%;">
                    Allowed File Type : [jpg, jpeg, gif, png]
                </label>
                <img style="margin:5px 0;width:700px;"
                src="'.$mediaUrl.'avatar/'.$partner['banner_pic'].'"
                />'
            ]
        );
        $fieldset->addField(
            'hot_tag',
            'select',
            [
                'name' => 'hot_tag',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('HOT TAG'),
                'title' => __('HOT TAG'),
                'value' => $partner['hot_tag'],
                'options' => [
                    '1' => __('Yes'),
                    '0' => __('No')
                ],
            ]
        );
        $fieldset->addField(
            'store_id',
            'select',
            [
                'name' => 'store_id',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Select Store'),
                'title' => __('Select Store'),
                'values' => $allStoreViews,
                'value' => $storeId,
                'after_element_html' => $data.'<script>
                require([
                    "jquery",
                    "plugins/DOMPurify"
                ], function($, DOMPurify){
                    var jQ = $.noConflict();
                    jQ("#marketplace_store_id").on("change", function() {
                        var storeId = DOMPurify.sanitize(jQ(this).val()?.toString());
                        window.location.href = DOMPurify.sanitize(jQ("#wk_mp_store"+storeId).val());
                    });
                });
                </script>'
            ]
        );

        $fieldset->addField(
            'enable_low_notification',
            'select',
            [
                'name' => 'enable_low_notification',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Enable Low Notification'),
                'title' => __('Enable Low Notification'),
                'value' => $partner['enable_low_notification'],
                'values' => [
                    ['value' => '1', 'label' => __('Yes')],
                    ['value' => '0', 'label' => __('No')],
                ],
                'after_element_html' => '<script>
            require(["jquery"], function($) {
                var jQ = $.noConflict();

                function toggleLowStockInput() {
                    var val = jQ("#enable_low_notification").val();
                    if (val === "1") {
                        jQ("#low_stock_quantity").prop("disabled", false);
                    } else {
                        jQ("#low_stock_quantity").prop("disabled", true).val("");
                    }
                }

                jQ(document).ready(function() {
                    toggleLowStockInput();
                    jQ("#enable_low_notification").on("change", toggleLowStockInput);
                });
            });
        </script>'
            ]
        );

        $fieldset->addField(
            'low_stock_quantity',
            'text',
            [
                'name' => 'low_stock_quantity',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Low Stock Quantity'),
                'title' => __('Low Stock Quantity'),
                'value' => $partner['low_stock_quantity'],
                'class' => '',
                'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "")'
            ]
        );


        $form->setUseContainer(true);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
