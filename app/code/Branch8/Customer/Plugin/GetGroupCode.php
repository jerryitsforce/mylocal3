<?php

namespace Branch8\Customer\Plugin;

class GetGroupCode{
    /**
     * @var \Branch8\Customer\Model\OrganizationFactory
     */
    protected $organizationFactory;

    /**
     * @param \Branch8\Customer\Model\OrganizationFactory $organizationFactory
     */
    public function __construct(
        \Branch8\Customer\Model\OrganizationFactory $organizationFactory
    ){
        $this->organizationFactory = $organizationFactory;
    }

    /**
     * @param $subject
     * @param $result
     * @return void
     */
    public function afterGetCustomerGroupCode($subject, $result){
        $organization = $subject->getOrganization();
        $organization = $this->organizationFactory->create()->load($organization);
        if($organization->getId()){
            return $organization->getName().' - '.$result;
        }
        return $result;
    }
}