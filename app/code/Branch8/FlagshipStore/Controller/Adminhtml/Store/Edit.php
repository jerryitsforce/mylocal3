<?php

namespace Branch8\FlagshipStore\Controller\Adminhtml\Store;

class Edit extends \Magento\Backend\App\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $resultPageFactory;
    /**
     * @var \Branch8\FlagshipStore\Model\FlagshipStoreFactory
     */
    protected $flagshipStoreFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Branch8\FlagshipStore\Model\FlagshipStoreFactory $flagshipStoreFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Branch8\FlagshipStore\Model\FlagshipStoreFactory $flagshipStoreFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->flagshipStoreFactory = $flagshipStoreFactory;
        parent::__construct($context);
    }
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute(){
        $resultPage = $this->resultPageFactory->create();
        $id = $this->getRequest()->getParam('id');
        $store = $this->flagshipStoreFactory->create()->load((int)$id);
        if(!$store->getId()){
            $resultPage->getConfig()->getTitle()->set(__('Create Flagship Store'));
        }
        return $resultPage;
    }

    /**
     * @return mixed
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_FlagshipStore::manage_store');
    }
}