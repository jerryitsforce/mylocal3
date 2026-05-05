<?php
namespace Branch8\Customer\Api;

interface CustomerManagementInterface
{
    /**
     * Get customer data by ID.
     *
     * @param int $customerId
     * @return \Branch8\Customer\Api\Data\BrowsingHistoryProductInterface[]
     */
    public function getCustomerBrowsingHistory(int $customerId);

}
