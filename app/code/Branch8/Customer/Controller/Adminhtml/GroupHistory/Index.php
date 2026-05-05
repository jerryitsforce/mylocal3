<?php

namespace Branch8\Customer\Controller\Adminhtml\GroupHistory;

class Index extends \Magento\Customer\Controller\Adminhtml\Index{
    /**
     * Get Seller's Flags list
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute(){
        $customerId = $this->initCurrentCustomer();
        $resultLayout = $this->resultLayoutFactory->create();
        $block = $resultLayout->getLayout()->getBlock('admin.customer.group.history');
        $block->setCustomerId($customerId)->setUseAjax(true);
        return $resultLayout;
    }
}