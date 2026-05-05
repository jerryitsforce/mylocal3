<?php
declare(strict_types=1);

namespace Branch8\EcpayInvoice\Plugin\Magento\Checkout\Model;

use Branch8\EcpayInvoice\Model\SaveEcPayParamsToQuoteAction;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;

class GuestPaymentInformationManagementPlugin
{

    private SaveEcPayParamsToQuoteAction $action;

    /**
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        SaveEcPayParamsToQuoteAction $action
    )
    {
        $this->action = $action;
    }

    /**
     * @param $subject
     * @param $cartId
     * @param $email
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return void
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        $subject,
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    )
    {
        /**
         * @var \Magento\Quote\Api\Data\PaymentExtensionInterface
         */
        if ($extensionAttributes = $paymentMethod->getExtensionAttributes()) {
            $this->action->saveEcPayInvoiceParams($cartId, [
                'ecpay_invoice_carruer_type' => $extensionAttributes->getEcpayInvoiceCarruerType(),
                'ecpay_invoice_type' => $extensionAttributes->getEcpayInvoiceType(),
                'ecpay_invoice_carruer_num' => $extensionAttributes->getEcpayInvoiceCarruerNum(),
                'ecpay_invoice_love_code' => $extensionAttributes->getEcpayInvoiceLoveCode(),
                'ecpay_invoice_customer_company' => $extensionAttributes->getEcpayInvoiceCustomerCompany(),
                'ecpay_invoice_customer_identifier' => $extensionAttributes->getEcpayInvoiceCustomerIdentifier()
            ]);
        }
    }
}
