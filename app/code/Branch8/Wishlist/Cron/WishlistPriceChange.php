<?php
namespace Branch8\Wishlist\Cron;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Store\Model\Store;
use Magento\Framework\DataObject;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;
use Magento\CatalogInventory\Model\Stock;

class WishlistPriceChange
{
    protected $_conn;

    protected $itemsColFactory;

    protected $productRepository;

    protected $wkHelper;

    protected $wlCache = [];

    protected $transportBuilder;

    protected $inlineTranslation;

    protected $scopeConfig;

    protected $storeManager;

    protected $productOptions = [];

    protected $variationsFactory;

    protected $stockRegistry;

    protected $getProductSalableQty;

    protected $isPreorderProduct;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory $itemsColFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Webkul\OptionsWithStockAndImages\Helper\Data $wkHelper,
        TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        VariationsFactory $variationsFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\InventorySales\Model\GetProductSalableQty $getProductSalableQty,
        \Branch8\Preorder\Model\IsPreorderProduct $isPreorderProduct
    )
    {
        $this->_conn = $resourceConnection->getConnection();
        $this->itemsColFactory = $itemsColFactory;
        $this->productRepository = $productRepository;
        $this->wkHelper = $wkHelper;

        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->variationsFactory = $variationsFactory;
        $this->stockRegistry = $stockRegistry;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->isPreorderProduct = $isPreorderProduct;
    }

    public function execute(){
        $isSendMail = $this->scopeConfig->getValue('wishlist/price_change_alert/send_mail_product_price_change');
        if(!$isSendMail){
            return;
        }
        $websiteId = 1;

        $itemsSlq = 'select wishlist_item_id, wishlist_item.wishlist_id, price, is_send_mail_price_change, wishlist.customer_id, 
            wishlist_item.product_id, customer_grid_flat.group_id
            from wishlist_item 
            left join wishlist on wishlist.wishlist_id = wishlist_item.wishlist_id
            left join customer_grid_flat on customer_grid_flat.entity_id = wishlist.customer_id
            where is_send_mail_price_change = 0';
        $queryItems = $this->_conn->query($itemsSlq);
        /**
         * array(customer_id => [items])
         */
        $needToSendMail = [];
        while($item = $queryItems->fetch()){
            $productOptionSpecify = [];
            try{
                $customerGroupId = $item['group_id'];
                $productId = $item['product_id'];
                $price = $item['price'];
                
                $wishlistItemId = $item['wishlist_item_id'];
                $sqlOptions = 'select code, value from wishlist_item_option where wishlist_item_id = '.$wishlistItemId.' and code <> "info_buyRequest"';
                $options = $this->_conn->fetchAll($sqlOptions);

                if(empty($options)){
                    $combKey = $productId.'_'.$customerGroupId;
                    if(isset($this->wlCache[$combKey])){
                        $finalPrice = $this->wlCache[$combKey]['price'];
                    }else{
                        $product = $this->productRepository->getById($productId, false, 1, false);
                        if(!$this->isSalable($product)){
                            continue;
                        }
                        $finalPrice = (float)$this->getFinalPrice($product->getId(), $customerGroupId, $websiteId);
                        $this->wlCache[$combKey] = ['price' => $finalPrice, 'name' => $product->getName(), 'url' => $product->getProductUrl()];
                    }
                    $sendData = [
                        'name' => $this->wlCache[$combKey]['name'],
                        'url' => $this->wlCache[$combKey]['url']
                    ];
                    if($finalPrice == $price){
                        continue;
                    }
                    $newPrice = $finalPrice;
                    $sendData['new_price'] = $newPrice;
                }else{
                    $customOptions = [];
                    $optionData = [];
                    foreach($options as $_opt){
                        if($_opt['code'] == 'option_ids'){
                            $optionIds = new DataObject(['value' => $_opt['value']]);
                            $customOptions['option_ids'] = $optionIds;
                        }
                        if($_opt['code'] == 'option_ids' || $_opt['code'] == 'info_buyRequest'){
                            continue;
                        }
                        $code = str_replace('option_', '', (string)$_opt['code']);
                        $optionData[] = [
                            'option_id' => $code,
                            'option_value' => $_opt['value']
                        ];
                        $customOptions[(string)$_opt['code']] = new DataObject(['value' => $_opt['value']]);
                    }
                    $comb = '';

                    $product = $this->productRepository->getById($productId, false, 1, false);
                    if(!$this->isSalable($product)){
                        continue;
                    }
                    $productOptionData = $this->getProductOptions($product);
                    /** Check variantion visible */
                    $optionVisible = 1;
                    foreach($optionData as $wlOption){
                        foreach($productOptionData as $optionId => $proOption){
                            if($wlOption['option_id'] == $optionId){
                                foreach($proOption['items'] as $itemId => $itemOption){
                                    if($itemId == $wlOption['option_value'] && (int)$itemOption['is_visible'] == 0){
                                        $optionVisible = 0;
                                    }
                                }
                            }
                        }
                    }
                    if(!$optionVisible){
                        continue;
                    }
                    foreach ($optionData as $option) {
                        if (isset($productOptionData[$option['option_id']]['items']) && isset($productOptionData[$option['option_id']]['items'][$option['option_value']])) {
                            $comb .= $productOptionData[$option['option_id']]['items'][$option['option_value']]['title'] . "_";
                            $productOptionSpecify[$productOptionData[$option['option_id']]['option_title']] = $productOptionData[$option['option_id']]['items'][$option['option_value']]['title'];
                        }
                    }

                    $comb = trim($comb, "_");
                    /** Validate variant stock */
                    $productVariationData = $this->getVariationsData($product->getRowId(), $comb);
                    if($productVariationData['stock'] <=0){
                        continue;
                    }
                    $combKey = $productId.'_'.$customerGroupId.'_'.$comb;
                    if(isset($this->wlCache[$combKey])){
                        $newPrice = $this->wlCache[$combKey]['price'];
                    }else{
                        $product->setCustomOptions($customOptions);
                        $newPrice = $this->getVariantFinalPrice($comb, $product->getId(), $customerGroupId, $websiteId);

                        $this->wlCache[$combKey] = ['price' => $newPrice, 'name' => $product->getName(), 'url' => $product->getProductUrl()];
                    }
                    $sendData = [
                        'name' => $this->wlCache[$combKey]['name'],
                        'url' => $this->wlCache[$combKey]['url']
                    ];
                    // $rowId = $product->getRowId();
                    // $variantData = $this->wkHelper->getCombData($rowId, $comb);
                    // $newPrice = ($variantData->getId()) ? $variantData->getPrice() : NULL;
                }
                
                
                if($newPrice === NULL){
                    continue;
                }

                if((float)$newPrice >= (float)$price){
                    continue;
                }
                $sendData['new_price'] = $newPrice;
                $sendData['options'] = $productOptionSpecify;
                $sendData['wl_item_id'] = $item['wishlist_item_id'];

                /** Prepair data */
                $customerId = $item['customer_id'];
                $needToSendMail[$customerId][] = $sendData;

            }catch(\Exception $e){

            }
        }
        foreach($needToSendMail as $customerId => $_sendItem){
            $customerSql = 'select firstname, buyer_email from customer_entity where entity_id='.$customerId;
            $customer = $this->_conn->fetchRow($customerSql);
            if(!$customer['firstname'] || !$customer['buyer_email']){
                continue;
            }
            try{
                /** Send mail */
                $templateVars = [
                    'items' =>$this->generateItems($_sendItem)
                ];
                
                $this->sendMail($customer['buyer_email'], $customer['firstname'], $templateVars);
                $this->updateWishlistItemSent(array_column($_sendItem, 'wl_item_id'));
            }catch(\Exception $e){

            }
        }
    
    }

    private function getVariationsData($rowId, $comb)
    {
        $collection = $this->variationsFactory->create()->getCollection();
        $collection->addFieldToFilter('product_id', $rowId);
        $collection->addFieldToFilter('comb', $comb);

        return $collection->getFirstItem();
    }

    protected function getProductOptions($product){
        if(!isset($this->productOptions[$product->getId()])){
            foreach ($product->getOptions() as $option) {
                $optType = $option->getType();
                if ($optType == "drop-down" || $optType == "drop_down" || $optType == "radio") {
                    $optionId = $option->getId();
                    $productOptionData[$optionId]['option_title'] = $option->getTitle();
                    foreach ($option->getValues() as $value) {
                        $valueId = $value->getId();
                        $productOptionData[$optionId]['items'][$valueId]['title'] = $value->getDefaultTitle();
                        $productOptionData[$optionId]['items'][$valueId]['is_visible'] = $value->getIsVisible();
                    }
                }
            }
            $this->productOptions[$product->getId()] = $productOptionData;
        }

        return $this->productOptions[$product->getId()];
    }

    protected function sendMail($recipientEmail, $recipientName, $templateVars){
        try {
            $this->inlineTranslation->suspend();
            $storeId = $this->storeManager->getStore()->getId();

            $sender = $this->scopeConfig->getValue(
                'wishlist/price_change_alert/sender_email_identity',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                1
            );

            $emailTemplate = $this->scopeConfig->getValue(
                'wishlist/price_change_alert/email_template',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($emailTemplate)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => Store::DISTRO_STORE_ID,
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope($sender)
                ->addTo($recipientEmail, $recipientName)
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            // Log the exception
        }
    }

    protected function generateItems($items){
        $html = '<div>';
        foreach($items as $_item){
            $html .= '<p><span>* 商品名稱 : </span> '.$_item['name'].'<br />';
            if(isset($_item['options']) && count($_item['options'])){
                $html .= '<div><span>* 選項</span></div>';
                foreach($_item['options'] as $optKey => $optVal){
                    $html .= '<div style="padding-left:40px">'.$optKey.': '.$optVal.'</div>';
                }
            }
            $html .= '<div><span>* 最新價格 : </span> '.$_item['new_price'].'</div>';
            $html .= '</p>';

            
            $html .= '<p>請點擊下方商品連結，查看商品詳細資訊並確認最新狀態。<br />';
            $html .= '<a href="'.$_item['url'].'">'.$_item['name'].'</a></p>';
            $html .= '<div>- - - -</div>';
        }
        $html .= '</div>';
        return $html;
    }

    protected function updateWishlistItemSent($itemIds){
        if(!count($itemIds)){
            return;
        }
        $this->_conn->update('wishlist_item', ['is_send_mail_price_change' => 1], 'wishlist_item_id IN ('.implode(',', $itemIds).')');
    }

    /**
     * Check is salable but not full Magento function, 
     * because the cron can't get login and customer group to check limit
     *
     * @return boolean
     */
    protected function isSalable($product){
        if (!$product->getId() || !$product->getSku() || !$product->getTypeId()) {
            return false;
        }

        if($product->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED){
            return false;
        }
        // Check custom option variation enable
        $stock = $this->stockRegistry->getStockItem($product->getId());
        if(!$stock->getManageStock()){
            return (bool)$product->getData('livesearch_instock');
        }
        $defaultStockId = Stock::DEFAULT_STOCK_ID;
        try{
            $salableQty = $this->getProductSalableQty->execute($product->getData('sku'), $defaultStockId);
            return $salableQty || $this->isPreorderProduct->checkIsPreorder($product->getId());
        }catch(\Throwable $e){
            return false;
        }
        return false;
    }

    public function getVariantFinalPrice($comb, $productId, $customerGroupId, $websiteId){
        $select = $this->_conn->select()
            ->from(['index_price' => 'branch8_variations_price_index'], ['final_price'])
            ->where('product_id = ?', $productId)
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('website_id = ?', $websiteId)
            ->where('variation_comb = ?', $comb);
    return (float)$this->_conn->fetchOne($select);
    }

    protected function getFinalPrice($productId, $customerGroupId, $websiteId){
        $select = $this->_conn->select()
            ->from(['index_price' => 'catalog_product_index_price'], ['final_price'])
            ->where('entity_id = ?', $productId)
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('website_id = ?', $websiteId);
        return (float)$this->_conn->fetchOne($select);
    }
}
