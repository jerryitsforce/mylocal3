<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab;

class PaymentInfo extends \Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Tab\PaymentInfo
{

    public const COMM_TEMPLATE = 'Branch8_SellerContactInformation::customer/paymentinfo.phtml';

    /**
     * @return array
     */
    public function getSellerInfo()
    {
        $partner = $this->customerEdit->getSellerInfoCollection();
        return $partner;
    }
}
