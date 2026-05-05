<?php
namespace Branch8\Quote\Plugin\Model;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;

class QuoteAddressValidator
{
    /**
     * @param \Magento\Quote\Model\QuoteAddressValidator $subject
     * @param CartInterface $cart
     * @param AddressInterface $address
     */
    public function beforeValidateForCart(
        \Magento\Quote\Model\QuoteAddressValidator $subject,
        CartInterface $cart,
        AddressInterface $address
    ): void {
        if ($cart->getCustomer() && $cart->getCustomer()->getId()) {
            $cart->setCustomerIsGuest(0);
        }
    }
}