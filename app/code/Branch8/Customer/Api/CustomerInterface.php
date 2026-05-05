<?php

namespace Branch8\Customer\Api;

interface CustomerInterface
{
    /**
     * @param string $member_seq
     * @param string $new_organization
     * @return \Branch8\Customer\Api\CustomerResponseInterface
     */
    public function updateOrganization($member_seq, $new_organization);

    /**
     * @param string $requestBody
     * @return \Branch8\Customer\Api\CustomerResponseInterface
     */
    public function updateOrganizationByPhone($requestBody);
}