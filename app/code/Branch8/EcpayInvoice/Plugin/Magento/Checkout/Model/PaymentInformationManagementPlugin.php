<?php

declare(strict_types=1);

namespace Branch8\EcpayInvoice\Plugin\Magento\Checkout\Model;

use Branch8\EcpayInvoice\Model\SaveEcPayParamsToQuoteAction;
use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Api\Data\PaymentInterface;
use Branch8\EcpayInvoice\Helper\Logger as LoggerInterface;

class PaymentInformationManagementPlugin
{

    private SaveEcPayParamsToQuoteAction $action;
    private CustomerRepositoryInterface $customerRepository;
    private CustomerSession $customerSession;
    private LoggerInterface $logger;

    /**
     * @param SaveEcPayParamsToQuoteAction $action
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerSession $customerSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        SaveEcPayParamsToQuoteAction $action,
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        LoggerInterface $logger
    ) {
        $this->action = $action;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    /**
     * @param PaymentInformationManagement $subject
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @return void
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagement $subject,
        $cartId,
        PaymentInterface $paymentMethod
    ) {
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

    /**
     * @param PaymentInformationManagement $subject
     * @param mixed $result
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @return mixed
     */
    public function afterSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagement $subject,
        $result,
        $cartId,
        PaymentInterface $paymentMethod
    ) {
        try {
            $extensionAttributes = $paymentMethod->getExtensionAttributes();
            $invoiceCarrier = $extensionAttributes->getEcpayInvoiceCarruerNum();
            if ($invoiceCarrier) {
                $customerId = $this->customerSession->getCustomerId();
                $customer = $this->customerRepository->getById($customerId);
                $customer->setCustomAttribute('invoice_carrier', $invoiceCarrier);
                $this->customerRepository->save($customer);
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
        return $result;
    }
}
