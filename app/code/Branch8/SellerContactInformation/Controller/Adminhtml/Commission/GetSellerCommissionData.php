<?php

namespace Branch8\SellerContactInformation\Controller\Adminhtml\Commission;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\Model\Auth\Session;


class GetSellerCommissionData extends \Magento\Backend\App\Action
{


    protected $resultJsonFactory;

    protected $mpHelperData;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Branch8\MarketplaceStaging\Helper\Data $mpHelperData
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->mpHelperData = $mpHelperData;
    }
    
    
    public function execute()
    {
        $sellerId = $this->getRequest()->getParam('seller_id');
        $resultJson = $this->resultJsonFactory->create();
        $sellerCommissionData = $this->mpHelperData->getCommisionRates((int)$sellerId);
        return $resultJson->setData($sellerCommissionData);
    }

        
}
