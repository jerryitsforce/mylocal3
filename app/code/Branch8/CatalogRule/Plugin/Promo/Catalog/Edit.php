<?php
namespace Branch8\CatalogRule\Plugin\Promo\Catalog;

use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class Edit
{
    protected $modifyHelper;

    protected $redirectFactory;

    protected $messageManager;

    public function __construct(
        \Branch8\CatalogRule\Helper\Modify $modifyHelper,
        MessageManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
    )
    {
        $this->modifyHelper = $modifyHelper;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
    }

    public function aroundExecute($subject, $process){
        $id = $subject->getRequest()->getParam('id');
        // if(!$this->modifyHelper->canEditCatalogRule($id)){
        //     $resultRedirect = $this->redirectFactory->create();
        //     $this->messageManager->addErrorMessage(__('Your rule is pending approval, you cannot edit it at this time.'));
        //     $resultRedirect->setPath('catalog_rule/promo_catalog/index'); 
        //     return $resultRedirect;
        // }
        $process();
        
    }
}
