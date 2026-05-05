<?php

namespace Branch8\Marketplace\Controller\Adminhtml\Tfa;

use Magento\Backend\App\Action\Context;
use Branch8\Frontend2FA\Model\SecretFactory;
use Magento\Ui\Component\MassAction\Filter;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;

class ResetSeller extends \Magento\Backend\App\Action{
   
    protected $_conn;

    protected $filter;

    protected $collectionFactory;
    
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ){
        parent::__construct($context);
        $this->_conn = $resourceConnection->getConnection();
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute(){
        $collection = $this->filter->getCollection(
            $this->collectionFactory->create()
        );
        $sellerIds = [];
        foreach ($collection as $item) {
            $sellerIds[] = $item->getSellerId();
        }

        if(!count($sellerIds)){
            $this->messageManager->addNoticeMessage(__('Please select account(s) to reset 2FA.'));
            return $this->_redirect('marketplace/seller/index');
        }

        $sqlReset = 'delete from branch8_frontend2fa_secrets where customer_id in('.implode(',', $sellerIds).')';
        try{
            $this->_conn->query($sqlReset);
            $this->messageManager->addSuccessMessage(__('Frontend 2FA for seller has been reset.'));
        }catch(\Exception $e){
            $this->messageManager->addErrorMessage(__('Frontend 2FA for seller has never been set.'));
        }

        return $this->_redirect('marketplace/seller/index');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_Marketplace::seller');
    }
}
