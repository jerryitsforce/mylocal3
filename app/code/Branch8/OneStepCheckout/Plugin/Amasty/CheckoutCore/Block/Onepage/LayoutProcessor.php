<?php

namespace Branch8\OneStepCheckout\Plugin\Amasty\CheckoutCore\Block\Onepage;

use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutCore\Block\Onepage\LayoutWalker;
use Amasty\CheckoutCore\Block\Onepage\LayoutWalkerFactory;

class LayoutProcessor
{
    /**
     * @var Config
     */
    private $checkoutConfig;

    /**
     * @var LayoutWalkerFactory
     */
    private $walkerFactory;

    /**
     * @var LayoutWalker
     */
    private $walker;

    private $customerSession;
    private $customerRepository;

    /**
     * @var \Branch8\GiftToFriend\Helper\Data
     */
    protected $b8GiftHelper;

    /**
     * @param Config $checkoutConfig
     * @param LayoutWalkerFactory $walkerFactory
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Branch8\GiftToFriend\Helper\Data $b8GiftHelper
     */
    public function __construct(
        Config $checkoutConfig,
        LayoutWalkerFactory $walkerFactory,
        \Magento\Customer\Model\Session $session,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Branch8\GiftToFriend\Helper\Data $b8GiftHelper
    ){
        $this->checkoutConfig = $checkoutConfig;
        $this->walkerFactory = $walkerFactory;
        $this->customerSession = $session;
        $this->customerRepository = $customerRepository;
        $this->b8GiftHelper = $b8GiftHelper;
    }

    /**
     * @param \Amasty\CheckoutCore\Block\Onepage\LayoutProcessor $subject
     * @param array $result
     * @return array
     */
    public function afterProcess(
        \Amasty\CheckoutCore\Block\Onepage\LayoutProcessor $subject,
        $result
    ) {
        if ($this->checkoutConfig->isEnabled() && !empty($result)) {
            // $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/layout.log');
            // $logger = new \Zend_Log();
            // $logger->addWriter($writer);

            $this->walker = $this->walkerFactory->create(['layoutArray' => $result]);
            //custom shipping step
            $this->walker->setValue('{CHECKOUT}.>>.steps.>>.shipping-step.>>.shippingAddress.>>.address-list.template','Branch8_OneStepCheckout/shipping-address/list');
            $this->walker->setValue('{CHECKOUT}.>>.steps.>>.shipping-step.>>.shippingAddress.>>.address-list.component','Branch8_OneStepCheckout/js/view/shipping-address/list');

            //customer info - Purchaser Information
            $customerEmail = $this->walker->getValue('{SHIPPING_ADDRESS}.>>.customer-email');
            $this->walker->unsetByPath('{SHIPPING_ADDRESS}.>>.customer-email');
            $this->walker->setValue('{CHECKOUT}.>>.steps.>>.shipping-step.>>.customer-email', $customerEmail);
            $this->walker->setValue('{CHECKOUT}.>>.steps.>>.shipping-step.>>.customer-email.component', 'Branch8_OneStepCheckout/js/view/form/element/email');
            $this->walker->setValue('{CHECKOUT}.>>.steps.>>.shipping-step.>>.customer-email.template', 'Branch8_OneStepCheckout/form/element/email-no-registration');

            //adjust label
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.firstname.label', __('Name'));
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.telephone.label', __('Phone Number'));

            //add placeholder
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.firstname.placeholder', __('請填寫收件人全名 (物流配送需真實姓名)'));
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.telephone.placeholder', __('Please enter mobile phone number'));
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.region.placeholder', __('請選擇縣市'));
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.region_id.inputNodePlaceholder', __('請選擇縣市')); //set placeholder for Region INPUT
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.city.placeholder', __('鄉鎮市區'));
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.street.>>.0.placeholder', __('Please enter the delivery address'));

            //hide custom field hotai_address_type on checkout
            $this->walker->unsetByPath('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.hotai_address_type');

            //hide custom address attribute of store address ( use class hidden-field )
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.cvs_store_code.additionalClasses', 'hidden-field');
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.cvs_store_name.additionalClasses', 'hidden-field');
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.cvs_store_servicetype.additionalClasses', 'hidden-field');
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.cvs_store_outside.additionalClasses', 'hidden-field');

            //hide zipcode field
            // $this->walker->unsetByPath('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.postcode');

            //hide country field ( use class hidden-field ) because we only use Taiwan now
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.country_id.additionalClasses', 'hidden-field');
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.city.additionalClasses', 'hidden-field');
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.postcode.value', '000');
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.postcode.additionalClasses', 'hidden-field');

            //remove telephone tooltip
            $this->walker->unsetByPath('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.telephone.config.tooltip');

            //sort field
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.telephone.sortOrder', 70);
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.street.sortOrder', 120);

            //same as purchaser form
            $sameAsPurchaserFieldset = $this->walker->getValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset');
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-fieldset', $sameAsPurchaserFieldset);
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-fieldset.displayArea', 'same-as-purchaser-fieldset');
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-convenience-fieldset', $sameAsPurchaserFieldset);
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-convenience-fieldset.displayArea', 'same-as-purchaser-convenience-fieldset');
            $giftToFiendFieldset = $this->walker->getValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset');
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.gift-to-friend-fieldset', $giftToFiendFieldset);
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.gift-to-friend-fieldset.displayArea', 'gift-to-friend-fieldset');

            $this->walker->unsetByPath('{SHIPPING_ADDRESS}.>>.same-as-purchaser-fieldset.>>.postcode');
            $this->walker->unsetByPath('{SHIPPING_ADDRESS}.>>.same-as-purchaser-convenience-fieldset.>>.postcode');

            if ($this->customerSession->isLoggedIn()) {
                $customerId = $this->customerSession->getCustomer()->getId();
                $customer = $this->customerRepository->getById($customerId);
                $firstName = $customer->getFirstName();
                $lastName = 'Hotai';//set a default lastName to pass Magento validate
                $phoneNumber = '';
                $phoneNumberAttribute = $customer->getCustomAttribute('phone_number');
                if ($phoneNumberAttribute && $phoneNumberAttribute->getValue()) {
                    $phoneNumber = $phoneNumberAttribute->getValue();
                }
                if ($firstName) {
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-fieldset.>>.firstname.additionalClasses', 'hidden-field');
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-fieldset.>>.firstname.value', $firstName);
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-convenience-fieldset.>>.firstname.additionalClasses', 'hidden-field');
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-convenience-fieldset.>>.firstname.value', $firstName);
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.gift-to-friend-fieldset.>>.firstname.value', '');
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.gift-to-friend-fieldset.>>.firstname.label', __("收禮人姓名"));
                }
                if ($lastName) {
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-fieldset.>>.lastname.additionalClasses', 'hidden-field');
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-fieldset.>>.lastname.value', $lastName);
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-convenience-fieldset.>>.lastname.additionalClasses', 'hidden-field');
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-convenience-fieldset.>>.lastname.value', $lastName);
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.lastname.additionalClasses', 'hidden-field');
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.lastname.value', $lastName);
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.gift-to-friend-fieldset.>>.lastname.additionalClasses', 'hidden-field');
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.gift-to-friend-fieldset.>>.lastname.value', $lastName);
                }
                if ($phoneNumber) {
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-fieldset.>>.telephone.additionalClasses', 'hidden-field');
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-fieldset.>>.telephone.value', $phoneNumber);
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-convenience-fieldset.>>.telephone.additionalClasses', 'hidden-field');
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-convenience-fieldset.>>.telephone.value', $phoneNumber);
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset.>>.telephone.value', $phoneNumber);
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.gift-to-friend-fieldset.>>.telephone.value', '');
                    $this->walker->setValue('{SHIPPING_ADDRESS}.>>.gift-to-friend-fieldset.>>.telephone.label', __("收禮人手機號碼"));
                }
                
                $this->walker->setValue('{SHIPPING_ADDRESS}.>>.gift-to-friend-fieldset.>>.region_id.label', __("收禮人地址"));
            }
            
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-convenience-fieldset.>>.region_id.additionalClasses', 'hidden-field');
            $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-convenience-fieldset.>>.city_id.additionalClasses', 'hidden-field');

            //change customScope and dataScope of shipping address
            if ($this->customerSession->isLoggedIn()) {
                $shippingAddressFieldset = $this->walker->getValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset');
                foreach ($shippingAddressFieldset['children'] as $key => &$field) {
                    if (isset($field['config']['customScope'])) {
                        $field['config']['customScope'] = 'newShippingAddress';
                    } else if ($key == 'region') {
                        $field['config'] = [];
                        $field['config']['customScope'] = 'newShippingAddress';
                    }
                    if (isset($field['dataScope'])) {
                        $field['dataScope'] = str_replace('shippingAddress', 'newShippingAddress', $field['dataScope']);
                    } else if ($key == 'region') {
                        $field['dataScope'] = 'newShippingAddress.region';
                    }
                    if ($key == 'region_id' && isset($field['config']['customEntry'])) {
                        $field['config']['customEntry'] = 'newShippingAddress.region';
                    }
                    if ($key == 'street' && isset($field['children'])) {
                        foreach ($field['children'] as &$data) {
                            if (isset($data['config']['customScope'])) {
                                $data['config']['customScope'] = 'newShippingAddress';
                            }
                        }
                    }
                    if ($key == 'city_id') {
                        $field['config']['customScope'] = 'newShippingAddress';
                        $field['dataScope'] = 'newShippingAddress.city_id';
                    }
                }
                $this->walker->setValue('{SHIPPING_ADDRESS}.>>.shipping-address-fieldset', $shippingAddressFieldset);

                $samAsPurchaserAddressFieldset = $this->walker->getValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-fieldset');
                foreach ($samAsPurchaserAddressFieldset['children'] as $key => &$field) {
                    if ($key == 'city_id') {
                        $field['config']['customScope'] = 'shippingAddress';
                        $field['dataScope'] = 'shippingAddress.city_id';
                    }
                }

                $samAsPurchaserConvenienceAddressFieldset = $this->walker->getValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-convenience-fieldset');
                foreach ($samAsPurchaserConvenienceAddressFieldset['children'] as $key => &$field) {
                    if ($key == 'city_id') {
                        $field['config']['customScope'] = 'shippingAddress';
                        $field['dataScope'] = 'shippingAddress.city_id';
                    }
                }

                $this->walker->setValue('{SHIPPING_ADDRESS}.>>.same-as-purchaser-fieldset', $samAsPurchaserAddressFieldset);

                $gitToFriendAddressFieldset = $this->walker->getValue('{SHIPPING_ADDRESS}.>>.gift-to-friend-fieldset');
                foreach ($gitToFriendAddressFieldset['children'] as $key => &$field) {
                    if (isset($field['config']['customScope'])) {
                        $field['config']['customScope'] = 'giftShippingAddress';
                    } else if ($key == 'region') {
                        $field['config'] = [];
                        $field['config']['customScope'] = 'giftShippingAddress';
                    }
                    if (isset($field['dataScope'])) {
                        $field['dataScope'] = str_replace('shippingAddress', 'giftShippingAddress', $field['dataScope']);
                    } else if ($key == 'region') {
                        $field['dataScope'] = 'giftShippingAddress.region';
                    }
                    if ($key == 'region_id' && isset($field['config']['customEntry'])) {
                        $field['config']['customEntry'] = 'giftShippingAddress.region';
                    }
                    if ($key == 'street' && isset($field['children'])) {
                        foreach ($field['children'] as &$data) {
                            // $logger->info(print_r(json_encode($field['children']), true));
                            if (isset($data['config']['customScope'])) {
                            // $logger->info(print_r($data['config']['customScope'], true));
                                $data['config']['customScope'] = 'giftShippingAddress';
                            }
                            
                            // $logger->info(print_r(json_encode($field['children']), true));
                        }
                    }
                    if ($key == 'city_id') {
                        $field['config']['customScope'] = 'giftShippingAddress';
                        $field['dataScope'] = 'giftShippingAddress.city_id';
                    }
                }
                $this->walker->setValue('{SHIPPING_ADDRESS}.>>.gift-to-friend-fieldset', $gitToFriendAddressFieldset);
            }

            //ECPay invoice
            $ECPayInvoice = $this->walker->getValue('{SHIPPING_ADDRESS}.>>.before-form.>>.custom_form');
            $this->walker->unsetByPath('{SHIPPING_ADDRESS}.>>.before-form.>>.custom_form');
            $this->walker->setValue('{CHECKOUT}.>>.steps.>>.shipping-step.>>.ecpay_invoice', $ECPayInvoice);

            //discount
            $discount = $this->walker->getValue('{SIDEBAR}.>>.summary_additional.>>.discount');
            if ($discount) {
                $this->walker->unsetByPath('{SIDEBAR}.>>.summary_additional.>>.discount');
                $this->walker->setValue('{CHECKOUT}.>>.steps.>>.shipping-step.>>.discount', $discount);
            }

            //referrer code
            $referrerCode = [
                'component' => 'Branch8_OneStepCheckout/js/view/additional/referrer-code',
                'template' => 'Branch8_OneStepCheckout/additional/referrer-code',
                'dataScope' => 'additional.referrer_code',
                'label' => __('Referrer Code'),
                'provider' => 'checkoutProvider',
                'visible' => true,
                'validation' => [
                    'required-entry' => true,
                    'validate-fullwidth-special' => true
                ]
            ];
            $this->walker->setValue('{CHECKOUT}.>>.steps.>>.shipping-step.>>.referrer_code', $referrerCode);

            //order note
            $orderNote = [
                'component' => 'Branch8_OneStepCheckout/js/view/additional/order-note',
                'template' => 'Branch8_OneStepCheckout/additional/order-note',
                'dataScope' => 'additional.order_note',
                'label' => __('Order Note'),
                'provider' => 'checkoutProvider',
                'visible' => true,
                'validation' => [
                    'required-entry' => true,
                    'validate-fullwidth-special' => true
                ]
            ];
            $this->walker->setValue('{CHECKOUT}.>>.steps.>>.shipping-step.>>.order_note', $orderNote);

            //remove gift card
            $giftCardAccount = $this->walker->getValue('{BILLING_STEP}.>>.payment.>>.afterMethods.>>.giftCardAccount');
            if(!empty($giftCardAccount)) {
                $this->walker->unsetByPath('{BILLING_STEP}.>>.payment.>>.afterMethods.>>.giftCardAccount');
            }

            $result = $this->walker->getResult();
        }

        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-convenience-fieldset']['children']['city_id'])) {
            $cityIdField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-convenience-fieldset']['children']['city_id'];
            $cityIdField['validation'] = [
                'required-entry' => false
            ];
            // $cityIdField['component'] = 'Branch8_OneStepCheckout/js/view/additional/country';
            // $cityIdField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/country';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-convenience-fieldset']['children']['city_id'] = $cityIdField;
        }

        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-convenience-fieldset']['children']['region_id'])) {
            $regionIdField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-convenience-fieldset']['children']['region_id'];
            $regionIdField['validation'] = [
                'required-entry' => false
            ];
            // $regionIdField['component'] = 'Branch8_OneStepCheckout/js/view/additional/country';
            // $regionIdField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/country';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-convenience-fieldset']['children']['region_id'] = $regionIdField;
        }

        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-convenience-fieldset']['children']['city_id'])) {
            $cityIdField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-convenience-fieldset']['children']['city_id'];
            $cityIdField['validation'] = [
                'required-entry' => false
            ];
            // $cityIdField['component'] = 'Branch8_OneStepCheckout/js/view/additional/country';
            // $cityIdField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/country';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-convenience-fieldset']['children']['city_id'] = $cityIdField;
        }

        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-convenience-fieldset']['children']['region_id'])) {
            $regionIdField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-convenience-fieldset']['children']['region_id'];
            $regionIdField['validation'] = [
                'required-entry' => false
            ];
            // $regionIdField['component'] = 'Branch8_OneStepCheckout/js/view/additional/country';
            // $regionIdField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/country';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-convenience-fieldset']['children']['region_id'] = $regionIdField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['firstname'])) {
            $firstNameField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['firstname'];
            $firstNameField['validation'] = [
                'required-entry' => true,
                'validate-fullwidth-special' => true
            ];
            $firstNameField['component'] = 'Branch8_OneStepCheckout/js/view/additional/input';
            $firstNameField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/input';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['firstname'] = $firstNameField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone'])) {
            $telephoneField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone'];
            $telephoneField['validation'] = [
                'required-entry' => true,
                'required-number' => true, 
                'validate-number' => true, 
                'validate-tw-phone' => true,
                'validate-fullwidth-special' => true,
                'minlength' => 10,
                'minlength' => 10
            ];
            $telephoneField['component'] = 'Branch8_OneStepCheckout/js/view/additional/telephone';
            $telephoneField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/telephone';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone'] = $telephoneField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['country_id'])) {
            $countryIdField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['country_id'];
            $countryIdField['validation'] = [
                'required-entry' => true
            ];
            $countryIdField['component'] = 'Branch8_OneStepCheckout/js/view/additional/country';
            // $countryIdField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/country';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['country_id'] = $countryIdField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id'])) {
            $regionIdField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id'];
            $regionIdField['validation'] = [
                'required-entry' => true
            ];
            // $regionIdField['component'] = 'Branch8_OneStepCheckout/js/view/additional/region';
            $regionIdField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/region';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id'] = $regionIdField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][0])) {
            $streetField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][0];
            $streetField['validation'] = [
               'required-entry' => true,
                'validate-fullwidth-special' => true
            ];
            $streetField['component'] = 'Branch8_OneStepCheckout/js/view/additional/input';
            $streetField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/input';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['street']['children'][0] = $streetField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['firstname'])) {
            $firstNameField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['firstname'];
            $firstNameField['validation'] = [
                'required-entry' => true,
                'validate-fullwidth-special' => true
            ];
            $firstNameField['component'] = 'Branch8_OneStepCheckout/js/view/additional/input';
            $firstNameField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/input';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['firstname'] = $firstNameField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['telephone'])) {
            $telephoneField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['telephone'];
            $telephoneField['validation'] = [
                'required-entry' => true,
                'required-number' => true, 
                'validate-number' => true, 
                'validate-tw-phone' => true,
                'validate-fullwidth-special' => true,
                'minlength' => 10,
                'minlength' => 10
            ];
            $telephoneField['component'] = 'Branch8_OneStepCheckout/js/view/additional/telephone';
            $telephoneField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/telephone';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['telephone'] = $telephoneField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['country_id'])) {
            $countryIdField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['country_id'];
            $countryIdField['validation'] = [
                'required-entry' => true
            ];
            $countryIdField['component'] = 'Branch8_OneStepCheckout/js/view/additional/country';
            // $countryIdField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/country';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['country_id'] = $countryIdField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['region_id'])) {
            $regionIdField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['region_id'];
            $regionIdField['validation'] = [
                'required-entry' => true
            ];
            // $regionIdField['component'] = 'Branch8_OneStepCheckout/js/view/additional/region';
            $regionIdField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/region';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['region_id'] = $regionIdField;
        }

        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['street']['children'][0])) {
            $streetField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['street']['children'][0];
            $streetField['validation'] = [
                'required-entry' => true,
                'validate-fullwidth-special' => true
            ];
            $streetField['component'] = 'Branch8_OneStepCheckout/js/view/additional/input';
            $streetField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/input';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['same-as-purchaser-fieldset']['children']['street']['children'][0] = $streetField;
        }

        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['firstname'])) {
            $firstNameField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['firstname'];
            $firstNameField['validation'] = [
                'required-entry' => false,
                'validate-fullwidth-special' => true
            ];
            $firstNameField['component'] = 'Branch8_OneStepCheckout/js/view/additional/input';
            $firstNameField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/input';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['firstname'] = $firstNameField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['telephone'])) {
            $telephoneField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['telephone'];
            $telephoneField['validation'] = [
                'required-entry' => false,
                'required-number' => true, 
                'validate-number' => true, 
                'validate-tw-phone' => true,
                'validate-fullwidth-special' => true,
                'minlength' => 10,
                'minlength' => 10
            ];
            $telephoneField['component'] = 'Branch8_OneStepCheckout/js/view/additional/telephone';
            $telephoneField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/telephone';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['telephone'] = $telephoneField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['country_id'])) {
            $countryIdField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['country_id'];
            $countryIdField['validation'] = [
                'required-entry' => true
            ];
            $countryIdField['component'] = 'Branch8_OneStepCheckout/js/view/additional/country';
            // $countryIdField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/country';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['country_id'] = $countryIdField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['region'])) {
            $regionField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['region'];
            $regionField['skipValidation'] = true;
            $regionField['validation'] = [
                'required-entry' => false
            ];
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['region'] = $regionField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['region_id'])) {
            $regionIdField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['region_id'];
            $regionIdField['skipValidation'] = true;
            $regionIdField['validation'] = [
                'required-entry' => false
            ];
            $regionIdField['component'] = 'Branch8_OneStepCheckout/js/view/additional/gift-region';
            // $regionIdField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/gift-region';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['region_id'] = $regionIdField;
        }

        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['street']['children'][0])) {
            $streetField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['street']['children'][0];
            $streetField['validation'] = [
                'required-entry' => false,
                'validate-fullwidth-special' => true
            ];
            $streetField['component'] = 'Branch8_OneStepCheckout/js/view/additional/input';
            $streetField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/input';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['street']['children'][0] = $streetField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['city'])) {
            $cityField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['city'];
            $cityField['skipValidation'] = true;
            $cityField['validation'] = [
                'required-entry' => false
            ];
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['city'] = $cityField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['city_id'])) {
            $cityIdField = $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['city_id'];
            $cityIdField['skipValidation'] = true;
            $cityIdField['validation'] = [
                'required-entry' => false
            ];
            $cityIdField['component'] = 'Branch8_OneStepCheckout/js/view/additional/gift-city-select';
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['gift-to-friend-fieldset']['children']['city_id'] = $cityIdField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['firstname'])) {
            $firstNameField = $result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['firstname'];
            $firstNameField['validation'] = [
                'required-entry' => true,
                'validate-fullwidth-special' => true
            ];
            $firstNameField['component'] = 'Branch8_OneStepCheckout/js/view/additional/input';
            $firstNameField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/input';
            $result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['firstname'] = $firstNameField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['telephone'])) {
            $telephoneField = $result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['telephone'];
            $telephoneField['validation'] = [
                'required-entry' => true,
                'required-number' => true, 
                'validate-number' => true, 
                'validate-tw-phone' => true,
                'validate-fullwidth-special' => true,
                'minlength' => 10,
                'minlength' => 10
            ];
            $telephoneField['component'] = 'Branch8_OneStepCheckout/js/view/additional/telephone';
            $telephoneField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/telephone';
            $result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['telephone'] = $telephoneField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['country_id'])) {
            $countryIdField = $result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['country_id'];
            $countryIdField['validation'] = [
                'required-entry' => true
            ];
            $countryIdField['component'] = 'Branch8_OneStepCheckout/js/view/additional/country';
            // $countryIdField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/country';
            $result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['country_id'] = $countryIdField;
        }
        
        if (isset($result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['region_id'])) {
            $regionIdField = $result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['region_id'];
            $regionIdField['validation'] = [
                'required-entry' => true
            ];
            // $countryIdField['component'] = 'Branch8_OneStepCheckout/js/view/additional/region';
            $regionIdField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/region';
            $result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['region_id'] = $regionIdField;
        }

        if (isset($result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['street']['children'][0])) {
            $streetField = $result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['street']['children'][0];
            $streetField['validation'] = [
                'required-entry' => true,
                'validate-fullwidth-special' => true
            ];
            $streetField['component'] = 'Branch8_OneStepCheckout/js/view/additional/input';
            $streetField['config']['elementTmpl'] = 'Branch8_OneStepCheckout/additional/input';
            $result['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']['children']['street']['children'][0] = $streetField;
        }

        // $logger->info(print_r(json_encode($result), true));
        
        return $result;
    }
}
