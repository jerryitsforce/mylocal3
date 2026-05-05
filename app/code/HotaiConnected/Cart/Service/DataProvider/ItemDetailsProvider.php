<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Cart\Service\DataProvider;

use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Model\ResourceModel\Url as CatalogUrl;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Branch8\GA4\Model\ProductHelper as GA4ProductHelper;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\MarketplacePreorder\Helper\Data as PreorderHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

class ItemDetailsProvider
{
    /**
     * @var CatalogUrl
     */
    private $catalogUrl;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ItemResolverInterface
     */
    private $itemResolver;

    /**
     * @var GA4ProductHelper
     */
    private $ga4ProductHelper;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PreorderHelper
     */
    private $preorderHelper;

    /**
     * @param CatalogUrl $catalogUrl
     * @param PriceHelper $priceHelper
     * @param ImageHelper $imageHelper
     * @param UrlInterface $urlBuilder
     * @param LoggerInterface $logger
     * @param ItemResolverInterface $itemResolver
     * @param GA4ProductHelper $ga4ProductHelper
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param PreorderHelper $preorderHelper
     */
    public function __construct(
        CatalogUrl $catalogUrl,
        PriceHelper $priceHelper,
        ImageHelper $imageHelper,
        UrlInterface $urlBuilder,
        LoggerInterface $logger,
        ItemResolverInterface $itemResolver,
        GA4ProductHelper $ga4ProductHelper,
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager,
        PreorderHelper $preorderHelper
    ) {
        $this->catalogUrl = $catalogUrl;
        $this->priceHelper = $priceHelper;
        $this->imageHelper = $imageHelper;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
        $this->itemResolver = $itemResolver;
        $this->ga4ProductHelper = $ga4ProductHelper;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager = $storeManager;
        $this->preorderHelper = $preorderHelper;
    }

    /**
     * Get item details array
     *
     * @param Item $item
     * @return array
     */
    public function getItemDetails(Item $item): array
    {
        $product = $item->getProduct();
        $imageUrl = $this->getProductImageUrl($item);

        return [
            'itemId' => (string)$item->getId(),
            'productId' => (string)$product->getId(),
            'productType' => $item->getProductType(),
            'isChecked' => (bool)$item->getAvailableToCheckout(),
            'isDisabled' => $this->getIsDisabled($item),
            'productUrl' => $this->getProductUrl($item),
            'productName' => $item->getName(),
            'imageUrl' => $imageUrl,
            'imageAlt' => $item->getName(),
            'qty' => (int)$item->getQty(),
            'qtyInputName' => 'cart[' . $item->getId() . '][qty]',
            'totalPrice' => $this->priceHelper->currency($item->getRowTotalInclTax(), true, false),
            'price' => $this->priceHelper->currency($item->getCalculationPrice(), true, false),
            'shippingMethod' => $this->getShippingMethod($item),
            'hasError' => $this->getHasError($item),
            'message' => $item->getMessage(),
            'options' => $this->getItemOptions($item),
            'productImage' => [
                'alt' => $item->getName(),
                'src' => $imageUrl,
                'width' => 150,
                'height' => 150
            ],
            'categories' => $this->getProductCategories($product),
            'price_range' => $this->getPriceRange($product)
        ];
    }

    /**
     * Get product URL
     *
     * @param Item $item
     * @return string
     */
    private function getProductUrl(Item $item): string
    {
        $product = $item->getProduct();

        if (!$product->isVisibleInSiteVisibility()) {
            $parentProduct = $item->getOptionByCode('product_type') !== null
                ? $item->getOptionByCode('product_type')->getProduct()
                : $product;

            $products = $this->catalogUrl->getRewriteByProductStore([
                $parentProduct->getId() => $item->getStoreId()
            ]);

            if (isset($products[$parentProduct->getId()])) {
                $urlDataObject = new \Magento\Framework\DataObject($products[$parentProduct->getId()]);
                $product->setUrlDataObject($urlDataObject);
            }
        }

        $productUrl = $product->getProductUrl();

        // Add from_cart parameter
        if ($productUrl) {
            $separator = strpos($productUrl, '?') !== false ? '&' : '?';
            $productUrl .= $separator . 'from_cart=1';
        }

        return $productUrl ?: '';
    }

    /**
     * Get product image URL
     *
     * @param Item $item
     * @return string
     */
    private function getProductImageUrl(Item $item): string
    {
        try {
            // Use ItemResolver to get the correct product for thumbnail (handles configurable products)
            $productForThumbnail = $this->itemResolver->getFinalProduct($item);

            // Use mini_cart_product_thumbnail image type (same as section/load)
            $imageHelper = $this->imageHelper->init($productForThumbnail, 'mini_cart_product_thumbnail');

            return $imageHelper->getUrl();
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get product image URL', [
                'item_id' => $item->getId(),
                'product_id' => $item->getProduct()->getId(),
                'exception' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Get shipping method for item
     *
     * @param Item $item
     * @return string
     */
    private function getShippingMethod(Item $item): string
    {
        $product = $item->getProduct();
        $shippingMethod = $product->getData('shipping_method');

        // 如果是電子票券，返回空字串
        if ($shippingMethod === 'electronic') {
            return '';
        }

        // 如果沒有配送方式，返回空字串
        if (!$shippingMethod) {
            return '';
        }

        // 分割配送方式
        $shippingMethods = explode(',', $shippingMethod);

        // 移除 electronic
        $shippingMethods = array_filter($shippingMethods, function($method) {
            return $method !== 'electronic';
        });

        // 只有一種配送方式時才返回，否則返回空字串（前端自行選擇）
        return count($shippingMethods) === 1 ? trim($shippingMethods[0]) : '';
    }

    /**
     * Get item options
     *
     * @param Item $item
     * @return array
     */
    private function getItemOptions(Item $item): array
    {
        $options = [];

        $product = $item->getProduct();
        $itemOptions = $item->getProduct()->getTypeInstance()->getOrderOptions($product);

        if (isset($itemOptions['options']) && is_array($itemOptions['options'])) {
            foreach ($itemOptions['options'] as $option) {
                $options[] = [
                    'custom_view' => false,
                    'label' => $option['label'] ?? '',
                    'optionId' => $option['option_id'] ?? '',
                    'optionType' => $option['option_type'] ?? '',
                    'printValue' => $option['print_value'] ?? '',
                    'value' => $option['value'] ?? ''
                ];
            }
        }

        // Handle configurable product options
        if ($item->getProductType() === 'configurable') {
            $configurableOptions = $item->getProduct()->getTypeInstance()->getSelectedAttributesInfo($product);
            foreach ($configurableOptions as $option) {
                $options[] = [
                    'custom_view' => false,
                    'label' => $option['label'] ?? '',
                    'optionId' => '',
                    'optionType' => 'drop_down',
                    'printValue' => $option['value'] ?? '',
                    'value' => $option['value'] ?? ''
                ];
            }
        }

        return $options;
    }

    /**
     * Get product categories with complete information
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    private function getProductCategories($product): array
    {
        $categories = [];

        try {
            // 取得主分類 ID（使用 GA4 的邏輯）
            $mainCategoryId = $this->ga4ProductHelper->getProductMainCategory($product);

            if (!$mainCategoryId) {
                return [];
            }

            // 從 product 取得 category collection 來獲得完整資訊
            $categoryCollection = $product->getCategoryCollection()
                ->addAttributeToSelect(['name', 'level', 'path'])
                ->addFieldToFilter('entity_id', $mainCategoryId);

            $mainCategory = $categoryCollection->getFirstItem();

            if (!$mainCategory->getId()) {
                return [];
            }

            // 解析 path 取得所有父分類 ID
            $pathIds = explode('/', $mainCategory->getPath());

            // 跳過 root (1) 和 default category (2)
            $pathIds = array_slice($pathIds, 2);

            // 取最後 4 層（與 GA4 一致）
            $pathIds = array_slice($pathIds, -4, 4);

            if (empty($pathIds)) {
                return [];
            }

            // 載入這些分類的完整資訊
            $categoriesCollection = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect(['name', 'level', 'path'])
                ->addFieldToFilter('entity_id', ['in' => $pathIds])
                ->setOrder('level', 'ASC'); // 按層級排序

            foreach ($categoriesCollection as $category) {
                $categories[] = [
                    'id' => (int)$category->getId(),
                    'name' => $category->getName(),
                    'level' => (int)$category->getLevel(),
                    'path' => $category->getPath()
                ];
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get product categories', [
                'product_id' => $product->getId(),
                'exception' => $e->getMessage()
            ]);
        }

        return $categories;
    }

    /**
     * Get product price range
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    private function getPriceRange($product): array
    {
        try {
            // 使用與 GA4 完全相同的方法取得價格
            $regularPrice = (float)$product->getPriceInfo()
                ->getPrice('regular_price')
                ->getValue();

            $finalPrice = (float)$product->getPriceInfo()
                ->getPrice('final_price')
                ->getValue();

            // 取得貨幣代碼
            $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();

            return [
                'minimum_price' => [
                    'regular_price' => [
                        'value' => $regularPrice,
                        'currency' => $currencyCode
                    ],
                    'final_price' => [
                        'value' => $finalPrice,
                        'currency' => $currencyCode
                    ]
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get product price range', [
                'product_id' => $product->getId(),
                'exception' => $e->getMessage()
            ]);

            // 返回預設值
            return [
                'minimum_price' => [
                    'regular_price' => [
                        'value' => 0,
                        'currency' => 'TWD'
                    ],
                    'final_price' => [
                        'value' => 0,
                        'currency' => 'TWD'
                    ]
                ]
            ];
        }
    }

    /**
     * Check if item has error
     *
     * @param Item $item
     * @return bool
     */
    private function getHasError(Item $item): bool
    {
        $hasError = $item->getHasError() === true;

        // 排除預訂商品
        if ($hasError && $this->preorderHelper->isPreorder($item->getProduct()->getId())) {
            return false;
        }

        return $hasError;
    }

    /**
     * Check if product is disabled
     *
     * @param Item $item
     * @return bool
     */
    private function getIsDisabled(Item $item): bool
    {
        $product = $item->getProduct();
        return $product->getStatus() == ProductStatus::STATUS_DISABLED;
    }
}
