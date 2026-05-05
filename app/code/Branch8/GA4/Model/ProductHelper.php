<?php
/**
 * @package
 * @author      Cuong Ho <cuonghh@forixwebdesign.com>
 * @copyright   Copyright © 2021 Forix LLC. All Rights Reserved. *
 */
declare(strict_types=1);

namespace Branch8\GA4\Model;

use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Manager;
use Magento\Store\Model\StoreManager;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\MpApi\Api\SellerManagementInterface;

class ProductHelper
{
    const CACHE_ID_CATEGORIES = 'branch8_ga4_cached_categories';
    /**
     * @var StoreManager
     */
    private $storeManager;
    /**
     * @var \Magento\Catalog\Helper\Product\Configuration
     */
    private $configurationHelper;
    /**
     * @var \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface
     */
    private $productOptionRepository;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category
     */
    private $resourceCategory;
    /**
     * @var
     */
    private $_gtmOptions;

    private $cacheState;
    /**
     * @var Manager
     */
    private $eventManager;
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;
    /**
     * @var Dimension
     */
    private $dimensionModel;
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var CatalogHelper
     */
    private CatalogHelper $catalogHelper;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    private \Webkul\Marketplace\Helper\Data $sellerHelper;

    /**
     * @var MarketplaceHelper
     */
    private MarketplaceHelper $marketplaceHelper;

    /**
     * @var SellerManagementInterface
     */
    private SellerManagementInterface $sellerManagement;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    private $storeCategories = [];
    private $sellerProductShopTitle = [];
    private $productsMainCategory = [];

    protected $calculatedTotal = 0;

    protected $brandLists = [];
    private Product\Attribute\Repository $attributeRepository;

    /**
     * @param \Magento\Catalog\Helper\Product\Configuration $configurationHelper
     * @param StoreManager $storeManager
     * @param \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $productOptionRepository
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category $resourceCategory
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param Dimension $dimension
     * @param Manager $eventManager
     * @param Session $checkoutSession
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\Catalog\Helper\Product\Configuration                   $configurationHelper,
        StoreManager                                                    $storeManager,
        \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface     $productOptionRepository,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category                   $resourceCategory,
        \Magento\Framework\App\Cache\StateInterface                     $cacheState,
        \Magento\Framework\App\CacheInterface                           $cache,
        Dimension                                                       $dimension,
        Manager                                                         $eventManager,
        Session                                                         $checkoutSession,
        \Magento\Framework\Pricing\PriceCurrencyInterface               $priceCurrency,
        CatalogHelper                                                   $catalogHelper,
        MarketplaceHelper                                               $marketplaceHelper,
        SellerManagementInterface                                       $sellerManagement,
        \Webkul\Marketplace\Helper\Data                                 $sellerHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Catalog\Model\Product\Attribute\Repository $attributeRepository

    ){
        $this->eventManager = $eventManager;
        $this->configurationHelper = $configurationHelper;
        $this->storeManager = $storeManager;
        $this->cacheState = $cacheState;
        $this->productOptionRepository = $productOptionRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->resourceCategory = $resourceCategory;
        $this->cache = $cache;
        $this->dimensionModel = $dimension;
        $this->priceCurrency = $priceCurrency;
        $this->checkoutSession = $checkoutSession;
        $this->catalogHelper = $catalogHelper;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->sellerManagement = $sellerManagement;
        $this->sellerHelper = $sellerHelper;
        $this->request = $request;
        $this->attributeRepository = $attributeRepository;
    }


    /**
     * @param $product
     * @return mixed
     */
    public function getGtmProductId($product)
    {
        return $product->getData('id');
    }

    /**
     * @param Product $product
     * @param $buyRequest
     * @param $wishlistItem
     * @param $checkForCustomOptions
     * @return false|string
     */
    public function checkVariantForProduct(
        Product $product,
                $buyRequest = [],
                $wishlistItem = null,
                $checkForCustomOptions = false
    )
    {
        $variant = [];
        /** get the configurable products variants, options */
        if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            $options = $product->getTypeInstance(true)->getSelectedAttributesInfo($product);
            foreach ($options as $option) {
                $variant[] = $option['label'] . ": " . $option['value'];
            }
            if (!$variant && isset($buyRequest['super_attribute'])) {
                $superAttributeLabels = [];
                $superAttributeOptions = [];
                $_attributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
                foreach ($_attributes as $_attribute) {
                    $superAttributeLabels[$_attribute['attribute_id']] = $_attribute['label'];
                    foreach ($_attribute->getOptions() as $option) {
                        $superAttributeOptions[$_attribute['attribute_id']][$option['value_index']] = $option['store_label'];
                    }
                }

                foreach ($buyRequest['super_attribute'] as $id => $value) {
                    try{
                        $variant[] = $superAttributeLabels[$id] . ": " . $superAttributeOptions[$id][$value];
                    }catch (\Exception $e){
                        continue;
                    }

                }
            }
        }

        $customOptionFound = false;
        /** This is for the custom options for products */
        $_customOptions = $product->getTypeInstance(true)->getOrderOptions($product);
        if (array_key_exists('options', $_customOptions)) {
            foreach ($_customOptions['options'] as $option) {
                $customOptionFound = true;
                $variant[] = $option['label'] . ": " . $option['print_value'];
            }
        }

        if ($wishlistItem && !$customOptionFound) {
            $options = $this->configurationHelper->getOptions($wishlistItem);
            foreach ($options as $customOption) {
                if (isset($customOption['print_value'])) {
                    $variant[] = $customOption['label'] . ": " . $customOption['print_value'];
                }
            }
        }

        /** Wishlist add to cart with not preselected custom options */
        if ($checkForCustomOptions && isset($buyRequest['options'])) {
            $productOptions = $this->productOptionRepository->getProductOptions($product);
            $productCustomOptionsLabel = [];
            $productCustomOptionsValues = [];
            foreach ($productOptions as $option) {
                $productCustomOptionsLabel[$option['option_id']] = $option['title'];
                if ($option->hasValues()) {
                    $values = $option->getValues();
                    foreach ($values as $value) {
                        $productCustomOptionsValues[$option['option_id']][$value->getOptionTypeId()] = $value->getTitle();
                    }
                }
            }

            foreach ($buyRequest['options'] as $optionId => $optionValues) {
                if (is_array($optionValues)) {
                    $optValue = [];
                    foreach ($optionValues as $value) {
                        $optValue[] = $productCustomOptionsValues[$optionId][$value];
                    }
                    $variant[] = $productCustomOptionsLabel[$optionId] . ": " . implode(',', $optValue);
                } elseif (isset($productCustomOptionsValues[$optionId])) {
                    $variant[] = $productCustomOptionsLabel[$optionId] . ": " . $productCustomOptionsValues[$optionId][$optionValues];
                } else {
                    $variant[] = $productCustomOptionsLabel[$optionId] . ": " . $optionValues;
                }
            }
        }

        if ($variant) {
            return implode(' | ', $variant);
        }

        return 'not_available';
    }

    private function _populateStoreCategories()
    {
        if (!empty($this->storeCategories)) {
            return;
        }
        $store = $this->storeManager->getStore();
        $storeId = $store->getStoreId();
        if ($storeId == 0) {
            $store = $this->storeManager->getDefaultStoreView();
            $rootCategoryId = $store->getRootCategoryId();
        } else {
            $rootCategoryId = $store->getRootCategoryId();
        }
        $cacheEnable = $this->cacheState->isEnabled(\Branch8\GA4\Model\Cache\Type::TYPE_IDENTIFIER);
        $cacheKey = self::CACHE_ID_CATEGORIES . '-' . $rootCategoryId . '-' . $storeId;
        if ($cacheEnable) {
            $this->eventManager->dispatch('branch8_ga4_cachekey_after', ['cache_key' => $cacheKey]);
            $cachedCategoriesData = (string)$this->cache->load($cacheKey);
            if ($cachedCategoriesData && ($decode = json_decode($cachedCategoriesData, true))) {
                $this->storeCategories = $decode;
                return;
            }
        }
        $categories = $this->categoryCollectionFactory->create()
            ->setStoreId($storeId)
            ->addAttributeToFilter('path', ['like' => "1/{$rootCategoryId}%"])
            ->addAttributeToSelect('name');
        foreach ($categories as $categ) {
            $this->storeCategories[$categ->getData('entity_id')] = [
                'name' => $categ->getData('name'),
                'path' => $categ->getData('path')
            ];
        }
        if ($cacheEnable) {
            $cachedCategories = json_encode($this->storeCategories);
            $this->cache->save($cachedCategories, $cacheKey, [\Branch8\GA4\Model\Cache\Type::CACHE_TAG]);
        }
    }

    private function _buildCategoryPath($categoryPath)
    {
        $categoryIds = explode('/', $categoryPath);
        $ignoreCategories = 3; //start from level 3 above: 1 is root, so index start from default category = 0
        $categoryIds = array_slice($categoryIds, $ignoreCategories);

        //get last 4 categories
        $categoryIds = array_slice($categoryIds, -4, 4);

        $categoriesWithNames = [];

        foreach ($categoryIds as $categoryId) {
            if (isset($this->storeCategories[$categoryId])) {
                $categoriesWithNames[] = $this->storeCategories[$categoryId]['name'];
            }
        }

        return $categoriesWithNames;
    }

    public function getGA4CategoriesFromCategoryIds($mainCategoryId)
    {
        $ga4Categories = [
            'item_category' => 'not_available',
            'item_category2' => 'not_available',
            'item_category3' => 'not_available',
            'item_category4' => 'not_available',
        ];

        if (!$mainCategoryId) {
            return $ga4Categories;
        }

        if (empty($this->storeCategories)) {
            $this->_populateStoreCategories();
        }

        $categoryId = $mainCategoryId;

        $categoryPath = '';
        if (isset($this->storeCategories[$categoryId])) {
            $categoryPath = $this->storeCategories[$categoryId]['path'];
        }

        $categories = $this->_buildCategoryPath($categoryPath);

        $index = 1;
        foreach ($categories as $categoryName) {
            $categoryKey = 'item_category' . ($index > 1 ? $index : '');
            $ga4Categories[$categoryKey] = $categoryName;
            $index += 1;
        }


        return $ga4Categories;
    }


    public function getCategoryHierarchy($categoryId)
    {
        if (empty($this->storeCategories)) {
            $this->_populateStoreCategories();
        }

        $categoryPath = '';
        if (isset($this->storeCategories[$categoryId])) {
            $categoryPath = $this->storeCategories[$categoryId]['path'];
        }

        return $this->_buildCategoryPath($categoryPath);
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getDetailProductPush(\Magento\Catalog\Model\Product $product, $index = 0, $isShow = true)
    {

        $productDetail = [];
        try {
            $productDetail['currency'] = $this->getCurrencyCode();
            $productDetail['item_name'] = $product->getName() ? @html_entity_decode($product->getName()) : '';
            $productDetail['item_id'] = (int)$product->getId();
//            $productDetail['item_brand'] = $product->getAttributeText('brand');

            /*if ($product->getData('brand') == null) {
                $product = $product->load($product->getId());
            }*/

            $productDetail['item_brand'] = $this->getBrand($product->getData('brand'));

            if (empty($productDetail['item_brand'])) {
                unset($productDetail['item_brand']);
            }

            $refererParams = $this->getParamsFromUrl();
//            $variant = $this->checkVariantForProduct($product);
//            if ($variant) {
//                $productDetail['item_variant'] = $variant;
//            }
            $mainCategory = $this->getProductMainCategory($product);

            $ga4Categories = $this->getGA4CategoriesFromCategoryIds($mainCategory);

            $productDetail = array_merge($productDetail, $ga4Categories);
            $productDetail = array_merge($productDetail, $refererParams);
            $productDetail['index'] = (int)$index;
            return $productDetail;
        }catch (\Exception $exception){
            return $productDetail;
        }

    }


    public function getBrand($brandId)
    {
        if (empty($brandId)) {
            return '';
        }
        $brandList = $this->getBrandList();
        return $brandList[$brandId] ?? '';
    }


    /**
     * @param $product
     * @return string
     */
    public function getProductMainCategory($product) {
        $productId = $product->getId();
        if (!isset($this->productsMainCategory[$productId])){
            // Try pre-loaded data first (Zero N+1)
            $this->productsMainCategory[$productId] = $product->getData('main_category') ?: $product->getResource()->getAttributeRawValue($productId,'main_category', $this->storeManager->getStore()->getId());
        }

        return (string)$this->productsMainCategory[$productId];
    }

    public function calculateGa4Total($products)
    {
        $this->calculatedTotal = 0;
        foreach ($products as $product) {
            $this->calculatedTotal += $product['quantity'] * $product['price'];
        }
        return $this->calculatedTotal;
    }

    /**
     * @param $collection
     * @return array
     */
    public function getGa4CommerceItemList($collection, $entityType)
    {
        $products = [];
        $index = 0;
        foreach ($collection as $item) {
            if(!$item->getAvailableToCheckout() && $entityType == 'quote'){
                continue;
            }
            $product = $item->getProduct();
            $productDetail = $this->getDetailProductPush($product, $index ,false);
            $index++;
            $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
            $price = $product->getPriceInfo()->getPrice('final_price')->getValue();
            $productDetail['item_name'] = @html_entity_decode($item->getName());
            $quantity = $this->formatQty($entityType == 'quote' ? (double)$item->getQty() : (double)$item->getQtyOrdered());
            $productDetail['quantity'] = $quantity;
            $productDetail['price'] = $this->formatMoney($price);
            $productDetail['affiliation'] = $this->getSellerByProductId($product->getRowId());
            $productDetail['discount'] = $this->formatMoney($regularPrice > $price ? $regularPrice - $price : 0);
            $this->calculatedTotal += $quantity * $price;
            $products[] = $productDetail;
        }

        return $products;
    }

    public function getGa4Total()
    {
        return $this->calculatedTotal;
    }

    /**
     * @param $product
     * @return array
     */
    public function getProductDimensions($product)
    {
        return $this->dimensionModel->getProductDimensions($product);
    }


    /**
     * @param $product
     * @return mixed|string
     */
    public function getSellerName($product)
    {

        $sellerId = $this->marketplaceHelper->getSellerIdByProductId($product->getId());

        $brand = 'Hotai';

        if (!empty($sellerId)) {
            $sellers = $this->sellerManagement->getSeller($sellerId);
            if ($sellers->getTotalCount() > 0) {
                $seller = $sellers->getItems()[0];
                $brand = $seller['company_name'] ?? $brand;
            }
        }

        return $brand;
    }

    public function formatMoney($value)
    {
        return round((float)$value);
    }

    public function formatQty($qty)
    {
        return round((float)$qty);
    }


    public function getCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * @param $product
     * @param $code
     * @return string
     */
    public function getLabelAttribute($product, $code)
    {
        $attribute = $product->getResource()->getAttribute($code);
        return $attribute ? $attribute->getFrontend()->getValue($product) : '';

    }

    /**
     * @param $qty
     * @param $product
     * @param $buyRequest
     * @param $checkForCustomOptions
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addToCartPushData($qty, $product, $buyRequest = [], $checkForCustomOptions = false)
    {
        $result = [];
        $result['event'] = 'add_to_cart';
        $result['type'] = $this->getItemType();
        $result['ecommerce'] = [];
        $result['ecommerce']['items'] = [];

        $index = $this->request->getParam('position', 0);

        $productData = $this->getDetailProductPush($product, $index);
        $productData['quantity'] = $this->formatQty($qty);
        $productData['item_variant'] = $this->checkVariantForProduct(
            $product,
            $buyRequest,
            null,
            $checkForCustomOptions
        );
        $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
        $price = $product->getPriceInfo()->getPrice('final_price')->getValue();
        $priceInclTax = $this->catalogHelper->getTaxPrice($product, $price, true);
        $productData['price'] = $this->formatMoney($priceInclTax);
        $productData['discount'] = $this->formatMoney($regularPrice > $price ? $regularPrice - $price : 0);
        $productData['affiliation'] = $this->getSellerByProductId($product->getRowId()); //TODO


        $result['ecommerce']['value'] = $this->formatMoney($productData['price'] * $qty);
        $result['ecommerce']['currency'] = $this->getCurrencyCode();
        $result['ecommerce']['items'][] = $productData;
        return $result;
    }

    public function getSelectItemCartPushData($qty, $product, $buyRequest = [])
    {
        $result = [];
        $result['event'] = Event::SELECT_ITEM_CART;

        $result['type'] = Event::SELECT_ITEM_CART_PRODUCT_CARD_ADD_TO_CART_ICON;

        //quickview
        if ($this->request->getParam('from_popup')) {
            $result['type'] = Event::SELECT_ITEM_CART_PRODUCT_POPUP_ADD_TO_CART_BTN;
        }

        $result['ecommerce'] = [];
        $result['ecommerce']['items'] = [];
        $result['ecommerce']['item_list_id'] = $this->request->getParam('item_list_id');
        $result['ecommerce']['item_list_name'] = $this->request->getParam('item_list_name');

        if($this->request->getParam('promotion_id') || $this->request->getParam('promotion_name')) {
            $result['ecommerce']['promotion_id'] = $this->request->getParam('promotion_id');
            $result['ecommerce']['promotion_name'] = $this->request->getParam('promotion_name');
        }

        $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
        $price = $product->getPriceInfo()->getPrice('final_price')->getValue();

        $productData = $this->getDetailProductPush($product);
        $productData['quantity'] = $this->formatQty($qty);
        $productData['price'] = $this->formatMoney($price);
        $productData['discount'] = $this->formatMoney($regularPrice > $price ? $regularPrice - $price : 0);
        $productData['affiliation'] = $this->getSellerByProductId($product->getRowId());
        $productData['item_variant'] = $this->checkVariantForProduct(
            $product,
            $buyRequest,
        );
        $result['ecommerce']['currency'] = $this->getCurrencyCode();
        $result['ecommerce']['items'][] = $productData;
        return $result;
    }


    /**
     * @param array $currentAddToCartData
     * @param array $addToCartPushData
     * @return array
     */
    public function mergeAddToCartPushData($currentAddToCartData, $addToCartPushData)
    {
        if (!is_array($currentAddToCartData)) {
            $currentAddToCartData = $addToCartPushData;
        } else {
            $currentAddToCartData['ecommerce']['items'][] = $addToCartPushData['ecommerce']['items'][0];
        }

        return $currentAddToCartData;
    }

    /**
     * @param $price
     * @return float|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function convertPriceToCurrentCurrency($price)
    {
        if ($this->getCurrencyCode() != $this->getBaseCurrencyCode()) {
            return $this->priceCurrency->convert($price, $this->storeManager->getStore(), $this->getCurrencyCode());
        }
        return $price;
    }

    /**
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBaseCurrencyCode()
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode();
    }

    /**
     * @param $product
     * @return array
     */
    public function addToComparePushData($product)
    {
        $result = [];
        $result['event'] = 'add_to_compare';
        $result['ecommerce'] = [];
        $result['ecommerce']['items'] = [];
        $productData = $this->getDetailProductPush($product);
        $productData['price'] = $this->formatMoney($product->getFinalPrice());
        $result['ecommerce']['value'] = (float)$productData['price'];
        $result['ecommerce']['currency'] = $this->getCurrencyCode();
        $result['ecommerce']['items'][] = $productData;
        return $result;
    }

    /**
     * @param $product
     * @param $buyRequest
     * @param $wishlistItem
     * @return array
     */
    public function addToWishListPushData($product, $buyRequest, $wishlistItem, $type = '')
    {
        if($this->request->getParam('type')){
            $type = $this->request->getParam('type');
        }
        $result = [];
        $result['event'] = 'add_to_wishlist';
        $result['type'] = $type != '' ? $type : $this->getItemType(true);
        $result['ecommerce'] = [];
        $result['ecommerce']['items'] = [];
        $productData = $this->getDetailProductPush($product);
        $productData['item_variant'] = $this->checkVariantForProduct($product, $buyRequest, $wishlistItem);
        $productData['quantity'] = $this->formatQty(isset($buyRequest['qty']) ? (double)$buyRequest['qty'] : 1);
        $productData['price'] = $this->formatMoney($product->getFinalPrice());
        $productData['affiliation'] = $this->getSellerByProductId($product->getId());
        $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
        $price = $product->getPriceInfo()->getPrice('final_price')->getValue();
        $productData['discount'] = $this->formatMoney($regularPrice > $price ? $regularPrice - $price : 0);
        $result['ecommerce']['value'] = $productData['price'];
        $result['ecommerce']['currency'] = $this->getCurrencyCode();
        $result['ecommerce']['items'][] = $productData;
        return $result;
    }

    public function getItemType($isWishlist = false)
    {
        if($this->request->getParam('form_type') == 'product_card') {
            return Event::ADD_TO_CARD_TYPE_PRODUCT_CARD;
        }

        $refererUrl = $this->request->getServer('HTTP_REFERER');
        if($this->request->getParam('from_popup')){
            return '商品卡_購物車_icon';
        }
        // Check if from cart page or has from_cart parameter
        $isFromCart = (@strpos($refererUrl, 'checkout/cart') !== false) ||
            (@strpos($refererUrl, 'from_cart=1') !== false);

        if ($isFromCart || $isWishlist) {
            return '購物車';
        }

        return '商品頁_landing';
    }

    protected function getParamsFromUrl()
    {
        $refererUrl = $this->request->getServer('HTTP_REFERER');
        $params = [
            'item_list_id' => '',
            'item_list_name' => '',
            'promotion_id' => '',
            'promotion_name' => ''
        ];

        if ($refererUrl) {
            $urlParts = parse_url($refererUrl);
            if (isset($urlParts['query'])) {
                parse_str($urlParts['query'], $refererParams);

                $params['item_list_id'] = $refererParams['item_list_id'] ?? '';
                $params['item_list_name'] = $refererParams['item_list_name'] ?? '';
                $params['promotion_id'] = $refererParams['promotion_id'] ?? '';
                $params['promotion_name'] = $refererParams['promotion_name'] ?? '';
            }
        }

        if ($this->request->getParam('item_list_id')) {
            $params['item_list_id'] = $this->request->getParam('item_list_id');
        }

        if ($this->request->getParam('item_list_name')) {
            $params['item_list_name'] = $this->request->getParam('item_list_name');
        }

        if ($this->request->getParam('promotion_id')) {
            $params['promotion_id'] = $this->request->getParam('promotion_id');
        }

        if ($this->request->getParam('promotion_name')) {
            $params['promotion_name'] = $this->request->getParam('promotion_name');
        }

        return $params;
    }

    public function removeFromCartPushData($qty, $product, $quoteItem)
    {
        /**
         * @var $quoteItem \Magento\Quote\Model\Quote\Item;
         */
        $result = [];
        $result['event'] = 'remove_from_cart';
        $result['ecommerce'] = [];
        $result['ecommerce']['items'] = [];
        $result['ecommerce']['currency'] = $this->getCurrencyCode();
        $productData = $this->getDetailProductPush($product);
        $productData['price'] = $this->formatMoney($quoteItem->getPriceInclTax());
        $result['ecommerce']['value'] = $productData['price'] * $qty;
        $productFromQuote = $quoteItem->getProduct();
        $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
        $price = $product->getPriceInfo()->getPrice('final_price')->getValue();
        $productData['quantity'] = $this->formatQty($qty);
        $productData['affiliation'] = $this->getSellerByProductId($product->getId());
        $productData['discount'] = $this->formatMoney($regularPrice > $price ? $regularPrice - $price : 0);
        $variant = $this->checkVariantForProduct($productFromQuote);
        if ($variant) {
            $productData['item_variant'] = $variant;
        }
        $result['ecommerce']['items'][] = $productData;
        return $result;
    }

    /**
     * @param $productId
     * @return string
     */
    /**
     * @param $productId
     * @param \Magento\Catalog\Model\Product|null $product
     * @return string
     */
    public function getSellerByProductId($productId, $product = null)
    {
        if (isset($this->sellerProductShopTitle['product'][$productId])) {
            return $this->sellerProductShopTitle['product'][$productId];
        }

        // Optimization: Try pre-loaded attribute first
        if ($product && $product->getData('seller_shop_name')) {
            $this->sellerProductShopTitle['product'][$productId] = $product->getData('seller_shop_name');
            return $this->sellerProductShopTitle['product'][$productId];
        }

        try {
            $seller = $this->sellerHelper->getSellerProductDataByProductId($productId);
            $sellerId = '';
            foreach ($seller as $value) {
                $sellerId = $value['seller_id'];
            }
            if($sellerId){
                if (isset($this->sellerProductShopTitle['seller'][$sellerId])) {
                    return $this->sellerProductShopTitle['seller'][$sellerId];
                }
                $sellerCollection = $this->sellerHelper->getSellerCollectionObj($sellerId);
                $sellerCollection->resetColumns();
                $fields = ["shop_title", "shop_url"];
                $sellerCollection->addFieldsToCollection($fields);
                $sellerInfo = $sellerCollection->getFirstItem()->getData();
                $shopTitle = $sellerInfo['shop_title'] ?? '';
                $shopUrl = $sellerInfo['shop_url'] ?? '';
                if (!$shopTitle) {
                    $shopTitle = $shopUrl;
                }
                $this->sellerProductShopTitle['product'][$productId] = $shopTitle;
                $this->sellerProductShopTitle['seller'][$sellerId] = $shopTitle;
                return $shopTitle;
            } else {
                $this->sellerProductShopTitle['product'][$productId] = '';
                return '';
            }
        }catch (\Exception $exception){
            return '';
        }

    }

    public function getBrandList()
    {
        if (empty($this->brandLists)) {
            $brandOptions = $this->attributeRepository->get('brand')->getOptions();
            foreach ($brandOptions as $_bOption){
                if ($_bOption->getValue() != '') {
                    $this->brandLists[$_bOption->getValue()] = $_bOption->getLabel();
                }
            }
        }

        return $this->brandLists;
    }

}
