<?php

namespace Branch8\MarketplaceSubAccount\Plugin\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;

class Data
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var HttpContext
     */
    protected $httpContext;
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scope
     * @param HttpContext $httpContext
     * @param Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scope,
        HttpContext $httpContext,
        Session $customerSession
    ){
        $this->scopeConfig = $scope;
        $this->httpContext = $httpContext;
        $this->customerSession = $customerSession;
    }

    public function aroundGetAccountGroup($subject, $process){
        $groupId = 1;
        $defaultSubAccountGroup = $this->scopeConfig->getValue('sellersubaccount/general_settings/sub_account_group');
        $coll = $subject->_groupCollection
            ->addFieldToFilter('customer_group_id', $defaultSubAccountGroup)
            ->getFirstItem();
        if($coll->getCustomerGroupId()){
            $groupId = $coll->getCustomerGroupId();
        }
        return $groupId;
    }

    public function aroundIsSubAccount($subject, $process)
    {
        $customerId = $this->httpContext->getValue('customer_id');
        if($customerId == null){
            $customerId = $this->customerSession->getCustomerId();
        }
        if(!$customerId){
            return 0;
        }

        $subAccount = $subject->_subAccountRepository->getByCustomerId($customerId);
        if ($subAccount->getId()) {
            return 1;
        }
        return 0;
    }

}