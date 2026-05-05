<?php

namespace Branch8\Checkout\Plugin\Model;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Checkout\Api\Data\ShippingInformationInterface;

class ShippingInformationManagementPlugin
{
    const LOG_PATH = 'Checkout/Addresses';

    /** @var HotaiCoreCommon $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    /**
     * @var CartRepositoryInterface
     */
    protected CartRepositoryInterface $quoteRepository;
    
    /**
     * @param HotaiCoreCommon $hotaiCoreCommon
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        HotaiCoreCommon $hotaiCoreCommon,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->hotaiCoreCommon              = $hotaiCoreCommon;
        $this->quoteRepository              = $quoteRepository;
    }


    /**
     * After plugin for saveAddressInformation
     * 
     * @param ShippingInformationManagement $subject
     * @param \Magento\Checkout\Api\Data\PaymentDetailsInterface $result
     * @param string $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return \Magento\Checkout\Api\Data\PaymentDetailsInterface
     */
    public function afterSaveAddressInformation(
        $subject,
        $result,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        // Retrieve the quote using the cart ID
        $quote = $this->quoteRepository->getActive($cartId);

        $this->writeLog('After saveAddressInformation-------');
        // Log the shipping method
        $shippingMethod = $addressInformation->getShippingMethodCode();
        $this->writeLog('Shipping Method: ' . $shippingMethod);
        
        // Log shipping address
        $shippingAddress = $addressInformation->getShippingAddress();
        $this->writeLog('Shipping Address: ' . json_encode($shippingAddress->getData()));

        // Log billing address
        $billingAddress = $addressInformation->getBillingAddress();
        if ($billingAddress) {
            $this->writeLog('Billing Address: ' . json_encode($billingAddress->getData()));
        }

        // Log the order ID if the order is created (i.e., after saving the address)
        if ($quote && $quote->getReservedOrderId()) {
            $orderId = $quote->getReservedOrderId();
            $this->writeLog('Order ID: ' . $orderId);
        }

        $this->writeLog('-------');

        return $result; // Always return the result
    }

    
    private function writeLog($message)
    {
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Checkout', 'quote_address')){
            $this->hotaiCoreCommon->writeLog(
                $message,
                self::LOG_PATH
            );
        }
    }

}