<?php

namespace Branch8\Customer\Controller\Adminhtml\Organization;

class Edit extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $resultPageFactory;

    protected $organizationFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Branch8\Customer\Model\OrganizationFactory $organizationFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->organizationFactory = $organizationFactory;
        parent::__construct($context);
    }
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute(){
        $resultPage = $this->resultPageFactory->create();
        $id = $this->getRequest()->getParam('id');
        $org = $this->organizationFactory->create()->load((int)$id);
        if(!$org->getId()){
            $resultPage->getConfig()->getTitle()->set(__('Add New Organization'));
        }
        return $resultPage;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_Customer::organization');
    }
}