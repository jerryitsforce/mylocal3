<?php

namespace Branch8\SellerContactInformation\Plugin\Block\Adminhtml\Customer\Edit;

use Magento\Customer\Controller\RegistryConstants;

class HideCustomerFieldset
{
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Webkul\Marketplace\Block\Adminhtml\Customer\Edit
     */
    protected $customerEdit;
    protected $request;

   /**
    * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit
    */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->coreRegistry = $registry;
        $this->customerEdit = $customerEdit;
        $this->request = $request;
    }

    /**
     * Function getCustomerId
     *
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * Function afterIsComponentVisible
     *
     * @return boolean
     */
    public function afterIsComponentVisible($subject, $result) {
        if ($result) {
            $coll = $this->customerEdit->getMarketplaceUserCollection();
            $isSeller = false;
            foreach ($coll as $row) {
                $isSeller = $row->getIsSeller();
            }
            $isSellerPanel = $this->request->getParam('seller_panel');
            if ($this->getCustomerId() && $isSeller && $isSellerPanel) {
                return false;
            }
        }

        return $result;
    }
}
