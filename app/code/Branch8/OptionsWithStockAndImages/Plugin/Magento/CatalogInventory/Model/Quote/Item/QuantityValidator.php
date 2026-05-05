<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Magento\CatalogInventory\Model\Quote\Item;

use Branch8\OptionsWithStockAndImages\Model\Actions\GetQuoteItemCombo;
use Branch8\OptionsWithStockAndImages\Model\Actions\IsVirtualProduct;
use Branch8\OptionsWithStockAndImages\Model\Actions\NormalizeCombo;
use Branch8\Yoxi\Helper\Common;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Model\Product;

class QuantityValidator
{
    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Logger\Logger
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    private GetQuoteItemCombo $getQuoteItemCombo;
    private Common $yoshiHelper;
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param \Webkul\OptionsWithStockAndImages\Helper\Data $helper
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\Registry $registry
     * @param GetQuoteItemCombo $getQuoteItemCombo
     * @param Common $yoshiHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param \Webkul\OptionsWithStockAndImages\Logger\Logger $logger
     */
    public function __construct(
        \Webkul\OptionsWithStockAndImages\Helper\Data $helper,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Registry $registry,
        GetQuoteItemCombo $getQuoteItemCombo,
        Common $yoshiHelper,
        ScopeConfigInterface $scopeConfig,
        \Webkul\OptionsWithStockAndImages\Logger\Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->yoshiHelper = $yoshiHelper;
        $this->getQuoteItemCombo = $getQuoteItemCombo;
        $this->helper = $helper;
        $this->cart = $cart;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\Event\Observer $observer
     * @return mixed|void
     */
    public function aroundValidate(
        \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $subject,
        \Closure $proceed,
        \Magento\Framework\Event\Observer $observer
    ) {
        /**
         * @var $item Item
         * @var $product \Magento\Catalog\Model\Product
         * @var $variantObject \Branch8\OptionsWithStockAndImages\Model\B8Variations
         * @var $extensions \Magento\Catalog\Api\Data\ProductExtension
         */
        $item = $observer->getEvent()->getItem();
        $product = $item->getProduct();
        $blockNotExistVariant = (bool) $this->scopeConfig->getValue('optionsWithStockAndImages/cart/block_not_exist_variant');
        $isVirtualProduct = $this->isVirtualProduct($product);
        if (!$product->hasOptions() || $isVirtualProduct)
            return $proceed($observer);
        $requestQuantity = $item->getQty();
        $variantCheck = trim($this->getQuoteItemCombo->get($item));
        if (!empty($variantCheck)) {
            $extensions = $product->getExtensionAttributes();
            $productVariants = $extensions->getVariations();
            $sku = $product->getSku();
            $normalizeMap = $extensions->getNormalizeVariationMap() ?? [];
            $variantCheck = NormalizeCombo::execute($variantCheck, $normalizeMap);
            $isExistVariant = isset($productVariants[$variantCheck]);
            $variantObject = $productVariants[$variantCheck] ?? null;
            if (!$isExistVariant && $blockNotExistVariant) {
                $item->addErrorInfo(
                    'cataloginventory',
                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                    __('The selected variation is not available now !')
                );
                $this->setFail($item);
                return;
            }
            if (!$isExistVariant) {
                $variantKeys = is_array($productVariants) ? array_keys($productVariants) : [];
                $this->logger->warning("Variant \"{$variantCheck}\" for SKU : \"$sku\" is not exist.");
                $this->logger->info("Variant:\"{$variantCheck}\", Product Variants:" . print_r($variantKeys, true) . ");");
            }

            if ($variantObject && ((float) $variantObject->getStock() - (float) $requestQuantity) < 0) {
                $item->addErrorInfo(
                    'cataloginventory',
                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                    __('The selected quantity exceeds the purchasable limit; unable to add to cart for now.')
                );
                $this->setFail($item);
                return;
            }
            return;
        }
        return $proceed($observer);
    }

    /**
     * @param Product $product
     * @return bool
     */
    private function isVirtualProduct(Product $product)
    {
        return IsVirtualProduct::check($product);
    }

    /**
     * @param Item $item
     * @return $this
     */
    private function setFail(Item $item)
    {
        if ($this->registry->registry('reload_cart')) {
            $this->registry->unregister('reload_cart');
        }
        $this->registry->register('reload_cart', true);
        $item->setAvailableToCheckout(0);
        return $this;
    }
}
