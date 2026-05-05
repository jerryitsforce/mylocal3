<?php

namespace Branch8\MarketplaceSubAccount\Plugin;

use Branch8\Customer\Helper\Data;
use Magento\Customer\CustomerData\Customer;
use Webkul\SellerSubAccount\Helper\Data as HelperData;

class CustomerData
{
    /**
     * @var Data
     */
    protected $b8CustomerHelper;
    /**
     * @var HelperData
     */
    protected $subAccountHelper;

    protected $customerSession;

    /**
     * @param Data $b8CustomerHelper
     * @param HelperData $subAccountHelper
     */
    public function __construct(
        Data $b8CustomerHelper,
        HelperData $subAccountHelper,
        \Magento\Customer\Model\Session $customerSession
    ){
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->subAccountHelper = $subAccountHelper;
        $this->customerSession = $customerSession;
    }
    public function afterGetSectionData(Customer $subject, array $result): array
    {
        $result['isSeller'] = 0;
        $result['isWaitForSeller'] = 0;
        $result['isSubAccount'] = 0;
        if($this->customerSession->getCustomerId()){
            $result['isSeller'] = $this->b8CustomerHelper->isSeller();
            $result['isWaitForSeller'] = $this->b8CustomerHelper->isWaitForSeller();
            $result['isSubAccount'] = $this->subAccountHelper->isSubAccount();
        }
        return $result;
    }
}