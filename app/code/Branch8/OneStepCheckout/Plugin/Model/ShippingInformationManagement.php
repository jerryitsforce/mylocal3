<?php

namespace Branch8\OneStepCheckout\Plugin\Model;

use Magento\Checkout\Api\Data\ShippingInformationInterface;

class ShippingInformationManagement
{
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
                                                              $cartId,
        ShippingInformationInterface                          $addressInformation
    ) {
        $shippingAddress = $addressInformation->getShippingAddress();
        if($shippingAddress->getExtensionAttributes() && $shippingAddress->getExtensionAttributes()->getStoreAddressInfo()){
            $shippingAddress->setStoreAddressInfo($shippingAddress->getExtensionAttributes()->getStoreAddressInfo());
        }
        return [$cartId, $addressInformation];
    }
}
