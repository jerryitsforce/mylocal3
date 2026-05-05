<?php

namespace Branch8\Customer\Plugin;

use Branch8\Customer\Helper\Organization;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\GroupFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;


class UpdateOrganization{

    protected $groupFactory;

    protected $organizationFactory;

    protected $groupHelper;

    protected $organizationCollectionFactory;

    public function __construct(
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Branch8\Customer\Model\OrganizationFactory $organizationFactory,
        \Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory $organizationCollectionFactory,
        \Branch8\Customer\Helper\Group $groupHelper
    ){
        $this->groupFactory = $groupFactory;
        $this->organizationFactory = $organizationFactory;
        $this->groupHelper = $groupHelper;
        $this->organizationCollectionFactory = $organizationCollectionFactory;
    }
    /**
     * Call Hotai API to get Organization
     * If the organization has changed, update the new level to customer
     * @param $subject
     * @param $customer
     * @param $customerId
     * @param $data
     * @return mixed
     */
    public function afterUpdateCustomerById($subject, $customer, $customerId, $data){
        //call API to get the Organization
        $newOrganization = '';


        //get current Organization
        $groupId = $customer->getGroupId();
        $group = $this->groupFactory->create()->load($groupId);
        $organizationId = $group->getData('organization');
        $organization = $this->groupFactory->create()->load($organizationId);
        $apiMappingName = $organization->getData('hotai1_name');

        if($newOrganization != $apiMappingName){
            //try set new organization
            $newOrganizationObj = $this->organizationCollectionFactory->create()
                ->addFieldToFilter('hotai1_name', $newOrganization)
                ->getFirstItem();
            if($newOrganizationObj->getId()){
                $actionLog = 'Login action, detect new organization '.$newOrganization;
                $this->groupHelper->setLevel($customerId, $newOrganizationObj->getId(), $actionLog);
            }else{
                $actionLog = 'Login action, detect new organization '.$newOrganization. ', but Can not find the New organization in Magento ,so Set to Default Organization';
                $this->groupHelper->setDefaultLevel($customerId, $actionLog);
            }

        }

        return $customer;
    }
}