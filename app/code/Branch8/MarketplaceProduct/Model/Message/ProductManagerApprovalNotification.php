<?php
namespace Branch8\MarketplaceProduct\Model\Message;

use \Magento\Framework\Notification\MessageInterface;

class ProductManagerApprovalNotification implements MessageInterface
{
    protected $mkConfig;

    protected $collectionManager;
    
    protected $cnt = 0;

    protected $urlBuilder;

    public function __construct(
        \Branch8\MarketplaceProduct\Helper\Config $mkConfig,
        \Branch8\MarketplaceProduct\Model\ResourceModel\ProductApprovalManagement\Grid\CollectionManager $collectionManager,
        \Magento\Framework\UrlInterface $urlBuilder
    ){
        $this->mkConfig = $mkConfig;
        $this->collectionManager = $collectionManager;
        $this->cnt = $this->collectionManager->count();
        $this->urlBuilder = $urlBuilder;
    }

    public function getIdentity() { 
        return hash('sha256', 'CATALOG_PRODUCT_MANAGER_APPROVAL'); 
    }
    public function isDisplayed() { 
        return $this->mkConfig->isAllowNegativeGrossProfit() && $this->cnt; 
    }
    public function getText() { 
        $url = $this->urlBuilder->getUrl('marketplacectrl/managerProduct/index');
        return __("There are %1 product review requests with negative gross profit. <a href='%2'>Review now</a>", $this->cnt, $url); 
    }

    public function getSeverity() { 
        return self::SEVERITY_NOTICE;
     }
}
