<?php

namespace Branch8\MarketplaceApi\Api;

interface SellerManagementInterface
{
    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param string $password
     * @param string $profileurl
     * @return mixed
     */
    public function createSellerAccount(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
                                                     $password,
                                                     $profileurl
    );
}