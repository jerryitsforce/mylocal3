<?php

namespace Branch8\OneStepCheckout\Plugin\Amasty\CheckoutCore\Model;

use Amasty\CheckoutCore\Model\Config;

class ConfigProvider
{
    /**
     * @var Config
     */
    private $checkoutConfig;

    /**
     * @param Config $checkoutConfig
     */
    public function __construct(
        Config $checkoutConfig
    ){
        $this->checkoutConfig = $checkoutConfig;
    }

    /**
     * @param \Amasty\CheckoutCore\Model\ConfigProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetCheckoutBlocksConfig(
        \Amasty\CheckoutCore\Model\ConfigProvider $subject,
        $result
    ) {
        if ($this->checkoutConfig->isEnabled() && !empty($result)) {
            if (isset($result[0])) { // $result[0] is layout config of column1
                //Add Section Purchaser Information
                $blockCustomerEmail = [];
                $blockCustomerEmail['name'] = 'customer_email';
                $blockCustomerEmail['title'] = __('Purchaser Information');
                array_unshift($result[0],$blockCustomerEmail);

                //Add Section for Ecpay invoice
                $blockECPayInvoiceConfig = [];
                $blockECPayInvoiceConfig['name'] = 'ecpay_invoice';
                $blockECPayInvoiceConfig['title'] = __('Ecpay Invoice Infomation');
                $result[0][] = $blockECPayInvoiceConfig;

                // if(isset($result[0][3]) && $result[0][3]['name'] == 'payment_method') {
                //     $blockPaymentMethodConfig = $result[0][3];
                //     array_splice($result[0], 3, 1);
                //     $result[0][] = $blockPaymentMethodConfig;
                // }

                //Add Section Discount Code
                $blockDiscountConfig = [];
                $blockDiscountConfig['name'] = 'discount';
                $blockDiscountConfig['title'] = __('Discount Code');
                $result[0][] = $blockDiscountConfig;

                //Add Section Referrer Code
                $blockReferrerCodeConfig = [];
                $blockReferrerCodeConfig['name'] = 'referrer_code';
                $blockReferrerCodeConfig['title'] = __('Referrer Code');
                $result[0][] = $blockReferrerCodeConfig;

                //Add Section Order Note
                $blockOrderNoteConfig = [];
                $blockOrderNoteConfig['name'] = 'order_note';
                $blockOrderNoteConfig['title'] = __('Order Note');
                $result[0][] = $blockOrderNoteConfig;
            }
        }
        return $result;
    }
}
