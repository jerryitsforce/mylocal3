<?php

namespace Branch8\FlagshipStore\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Sales extends AbstractHelper
{
    const CART_FLAGSHIP_STORE_PREFIX = 'flagship_';
    /**
     * @var
     */
    protected $resourceConnection;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $conn;
    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $categoryRepository;
    /**
     * @var \Magento\Backend\Model\Url
     */
    protected $backendUrlManager;

    /**
     * @param Context $context
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
     * @param \Magento\Backend\Model\Url $backendUrlManager
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Backend\Model\Url $backendUrlManager
    ){
        $this->conn = $resourceConnection->getConnection();
        $this->categoryRepository = $categoryRepository;
        $this->backendUrlManager = $backendUrlManager;
        parent::__construct($context);
    }

    public function getSellerIdFromProductId($pid)
    {
        $query = $this->conn->select()
            ->from(['mp_product' => 'marketplace_product'], ['seller_id'])
            ->where('mageproduct_id=?', $pid)
            ->limit(1);
        return $this->conn->fetchOne($query);
    }


    public function getFlagshipStoreFromSellerId($sid)
    {
        $storeQuery = $this->conn->select()
            ->from(['flss' => 'flagship_store_seller'], 'flagship_store_id')
            ->joinLeft(['fls' => 'flagship_store'], 'fls.entity_id = flss.flagship_store_id', [])
            ->where('is_active = ?', 1)
            ->where('seller_id = ?', $sid);
        return $this->conn->fetchOne($storeQuery);
    }

    public function getFlagshipStoreIdFromProductId($pid)
    {
        $sellerId = $this->getSellerIdFromProductId($pid);
        $storeQuery = $this->conn->select()
            ->from(['flss' => 'flagship_store_seller'], 'flagship_store_id')
            ->where('seller_id', $sellerId);
        return $this->conn->fetchOne($storeQuery);
    }

    public function getFlagshipStoreData($flagshipId)
    {
        $flagshipId = str_replace(self::CART_FLAGSHIP_STORE_PREFIX, '', $flagshipId);
        $flagshipQuery = $this->conn->select()
            ->from(['fls' => 'flagship_store'], ['category_id'])
            ->where('entity_id = ?', $flagshipId);
        $categoryId = $this->conn->fetchOne($flagshipQuery);
        $category = $this->categoryRepository->get($categoryId);
        return [
            'name' => $category->getName(),
            'shop_title' => $category->getName(),
            'url' => $category->getUrl(),
            'shop_url' => $category->getUrl()
        ];
    }

    public function getIntersectionSellerShippingMethod($flagshipStoreId)
    {
        $flagshipStoreId = str_replace(self::CART_FLAGSHIP_STORE_PREFIX, '', $flagshipStoreId);
        $sellerIdsQuery = $this->conn->select()
            ->from(['flss' => 'flagship_store_seller'], ['seller_id'])
            ->where('flagship_store_id = ?', $flagshipStoreId);
        $sellerIds = $this->conn->fetchCol($sellerIdsQuery);
        $sellerDataQuery = $this->conn->select()
            ->from(['mp_userdata' => 'marketplace_userdata', ['seller_id', 'shipping_method']])
            ->where('seller_id in(?)', implode(',', $sellerIds));
        $sellerShippingMethods = $this->conn->fetchAll($sellerDataQuery);
        $intersectMethod = [];
        foreach($sellerShippingMethods as $_sellerData){
            $sMethods = explode(',', (string)$_sellerData['shipping_methods']);
            if(empty($intersectMethod)){
                $intersectMethod = $sMethods;
            }else{
                $intersectMethod = array_intersect($intersectMethod, $sMethods);
            }
        }
        return $intersectMethod;
    }

    public function getFlagshipStoreCart($sid)
    {
        $query = $this->conn->select()
            ->from(['flss' => 'flagship_store_seller'], ['seller_id'])
            ->joinLeft(['fls' => 'flagship_store'], 'flss.flagship_store_id = fls.entity_id', ['main_seller', 'entity_id'])
            ->where('seller_id = ?', (int)$sid)
            ->where('fls.is_active = ?', 1)
            ->limit(1);
        return $this->conn->fetchRow($query);
    }

    public function isMainSellerOfFlagshipStore($sid)
    {
        $query = $this->conn->select()
            ->from(['flss' => 'flagship_store_seller'], ['cnt' => 'count(*)'])
            ->joinLeft(['fls' => 'flagship_store'], 'flss.flagship_store_id = fls.entity_id', [])
            ->where('seller_id = ?', (int)$sid)
            ->where('fls.main_seller = ?', (int)$sid);
        return $this->conn->fetchOne($query);
    }

    public function getFlagshipStoreInfor($flagshipStoreId)
    {
        $flagshipQuery = $this->conn->select()
            ->from(['fls' => 'flagship_store'], '*')
            ->where('entity_id = ?', $flagshipStoreId);
        return $this->conn->fetchRow($flagshipQuery);
    }

    public function getProcessOrderValue($parentOrderId)
    {
        $query = $this->conn->select()
            ->from(['pco' => 'sales_parent_order_children'], [])
            ->joinLeft(['so' => 'sales_order'], 'so.entity_id = pco.children_id and is_flagship_store_process_order is not null', [])
            ->columns([new \Zend_Db_Expr('sum(`grand_total`) as total_process_value')])
            ->where('pco.parent_id = ?', $parentOrderId)
            ->group('pco.parent_id');
        return (int)$this->conn->fetchOne($query);
    }

    public function getProcessOrderValues($parentOrderId)
    {
        /**
         * The flagship process order has subtotal = grand total, subtotal_incl_tax = grand_total
         * because there are no coupon and shipping
         */
        $query = $this->conn->select()
            ->from(['pco' => 'sales_parent_order_children'], [])
            ->joinLeft(['so' => 'sales_order'], 'so.entity_id = pco.children_id and is_flagship_store_process_order is not null', [])
            ->columns([
                new \Zend_Db_Expr('sum(`subtotal`) as subtotal'),
                new \Zend_Db_Expr('sum(`subtotal_incl_tax`) as subtotal_incl_tax')
            ])
            ->where('pco.parent_id = ?', $parentOrderId)
            ->group('pco.parent_id');
        return $this->conn->fetchRow($query);
    }

    public function getOrderUrls($incrementIds){
        $orderData = $this->getOrderIdByIncrementId($incrementIds);
        $urls = [];
        foreach($orderData as $_order){
            $urls[$_order['increment_id']] = $this->backendUrlManager->getUrl('sales/order/view', ['order_id' => $_order['entity_id']]);
        }
        return $urls;
    }

    public function getOrderIdByIncrementId($incrementIds)
    {
        $arrIncrementIds = explode(',', (string)$incrementIds);
        $query = $this->conn->select()
            ->from(['so' => 'sales_order'], ['entity_id', 'increment_id'])
            ->where('increment_id in (?)', $arrIncrementIds);
        $orderData = $this->conn->fetchAll($query);
        return $orderData;
    }

    public function isProductShippingFee($item){

        $product = $item->getProduct();
        $isFlagshipProductShipping = $product->getData('flagship_store_process_seller_id');
        return (int)$isFlagshipProductShipping;
    }

}