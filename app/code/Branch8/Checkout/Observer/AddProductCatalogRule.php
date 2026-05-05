<?php

namespace Branch8\Checkout\Observer;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
class AddProductCatalogRule implements \Magento\Framework\Event\ObserverInterface{

    protected $resourceConnection;

    protected $storeManager;

    protected $_localeDate;

    protected $rule;
    public function __construct(
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\CatalogRule\Model\ResourceModel\Rule $rule

    ){
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->_localeDate = $localeDate;
        $this->rule = $rule;
    }

    /**
     * Phase 1: apply for simple, virtual
     * @param $observer
     * @return void
     */
    public function execute($observer){

        $order = $observer->getEvent()->getOrder();
        $connection = $this->resourceConnection->getConnection();

        $customerGroupId = $order->getCustomerGroupId();
        $storeId = $order->getStoreId();
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        $orderItems = $order->getAllItems();
        foreach($orderItems as $_item) {

            $productId = $_item->getProductId();
            $dateTs = $this->_localeDate->scopeTimeStamp($storeId);
            $rulesData = $this->rule->getRulesFromProduct($dateTs, $websiteId, $customerGroupId, $productId);

            $itemId = $_item->getId();
            $logPrice = json_encode($rulesData);
            $sqlUpdateOrderItem = "update sales_order_item set price_log='".$logPrice."' where item_id=".$itemId;
            $connection->query($sqlUpdateOrderItem);
        }

    }


}