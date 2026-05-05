<?php

namespace Branch8\Preorder\Plugin;

use Branch8\OptionsWithStockAndImages\Model\Actions\CheckStockCombo;
use Branch8\OptionsWithStockAndImages\Model\Actions\GetQuoteItemCombo;
use Branch8\OptionsWithStockAndImages\Model\Actions\GetStockCombo;
use Branch8\OptionsWithStockAndImages\Model\Actions\NormalizeCombo;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\Registry;

class QuantityValidator
{
    /**
     * @var \Webkul\Preorder\Helper\Data
     */
    private $_preorderHelper;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var StockStateInterface
     */
    protected $stockState;

    protected $observer;

    protected $registry;
    private CheckStockCombo $checkStockCombo;
    /**
     * @var GetQuoteItemCombo
     */
    private GetQuoteItemCombo $getQuoteItemCombo;
    /**
     * @var GetStockCombo
     */
    private GetStockCombo $getStockCombo;

    /**
     * @param \Webkul\MarketplacePreorder\Helper\Data $preorderHelper
     * @param StockRegistryInterface $stockRegistry
     * @param StockStateInterface $stockState
     * @param \Magento\Framework\Event\Observer $observer
     * @param Registry $registry
     * @param GetQuoteItemCombo $getQuoteItemCombo
     * @param GetStockCombo $getStockCombo
     * @param CheckStockCombo $checkStockCombo
     */
    public function __construct(
        \Webkul\MarketplacePreorder\Helper\Data $preorderHelper,
        StockRegistryInterface                  $stockRegistry,
        StockStateInterface                     $stockState,
        \Magento\Framework\Event\Observer       $observer,
        Registry                                $registry,
        GetQuoteItemCombo                       $getQuoteItemCombo,
        GetStockCombo                           $getStockCombo,
        CheckStockCombo                         $checkStockCombo
    )
    {
        $this->_preorderHelper = $preorderHelper;
        $this->stockRegistry = $stockRegistry;
        $this->stockState = $stockState;
        $this->observer = $observer;
        $this->registry = $registry;
        $this->getQuoteItemCombo = $getQuoteItemCombo;
        $this->checkStockCombo = $checkStockCombo;
        $this->getStockCombo = $getStockCombo;
    }

    /**
     * @param \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\Event\Observer $observer
     * @return mixed|true|void
     */
    public function aroundValidate(
        \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $subject,
        \Closure                                                     $proceed,
        \Magento\Framework\Event\Observer                            $observer
    )
    {
        $quoteItem = $observer->getEvent()->getItem();
        $product = $quoteItem->getProduct();
        $productId = $product->getId();
        /**
         * Check if product in stock. For composite products check base (parent) item stock status
         */
        if ($quoteItem->getParentItem()) {
            $product = $quoteItem->getParentItem()->getProduct();
            $productId = $product->getId();
        }
        $isPreorder = $this->_preorderHelper->isPreorder($productId);
        if (!$isPreorder) {
            return $proceed($observer);
        }
        list($qtyCheck, $combo) = $this->qtyCheck($product, $quoteItem);
        if (!$qtyCheck) {
            $name = $combo ? sprintf('%s (%s)', $quoteItem->getName(), $combo) : $quoteItem->getName();
            $quoteItem->addErrorInfo(
                'cataloginventory',
                \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                __('Requested Quantity(s) to preorder this product is not available')
            );
            return;
        }
        /******* Check seller specfication PreorderBuyer or all *******/
        $StockDetails = $this->_preorderHelper->getStockDetails($productId);
        if ($this->_preorderHelper->isPreorderActive($productId)
            &&
            $this->_preorderHelper->isPendingPreorder($productId)
            &&
            $StockDetails['is_in_stock'] && !$this->registry->registry('split_cart_adding_remaining_items_to_cart')) {
            $quoteItem->addErrorInfo(
                'cataloginventory',
                \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                __('This product is preorder,firstly completed preorder')
            );

            return;
        }
        if ($this->_preorderHelper->isPreorder($productId) || $this->_preorderHelper->isConfigPreorder($productId)) {
            return true;
        } else {
            return $proceed($observer);
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return array
     */
    private function qtyCheck(\Magento\Catalog\Model\Product $product, \Magento\Quote\Model\Quote\Item $quoteItem)
    {
        if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return $this->configurableQtyCheck($product, $quoteItem);
        }

        return $this->simpleQtyCheck($product, $quoteItem);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return array
     */
    private function configurableQtyCheck(\Magento\Catalog\Model\Product $product, \Magento\Quote\Model\Quote\Item $quoteItem)
    {
        $qtyCheck = true;
        $combo = $this->getQuoteItemCombo->get($quoteItem);
        $requestQty = $quoteItem->getQty();
        $usedProductIds = $product->getTypeInstance()->getUsedProductIds($product);
        foreach ($usedProductIds as $usedProductId) {
            if ($this->_preorderHelper->isPreorder($usedProductId)) {
                $productPreorder = $this->_preorderHelper->usedProductIdPreorder($usedProductId);
                $preorderQty = $productPreorder->getWkMppreorderQty();
                if ($preorderQty && (int)$preorderQty < $requestQty) {
                    $qtyCheck = false;
                }
            }
        }
        return [$qtyCheck, $combo];
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return array
     */
    private function simpleQtyCheck(\Magento\Catalog\Model\Product $product, \Magento\Quote\Model\Quote\Item $quoteItem)
    {
        $qtyCheck = true;
        $preorderQty = (int)$product->getWkMppreorderQty();
        $combo = $this->getQuoteItemCombo->get($quoteItem);
        if ($preorderQty && (int)$preorderQty < $quoteItem->getQty()) {
            $qtyCheck = false;
        } elseif ($combo) {
            $extensions = $product->getExtensionAttributes();
            $normalizeMap = ($extensions && method_exists($extensions, 'getNormalizeVariationMap'))
                ? ($extensions->getNormalizeVariationMap() ?? [])
                : [];
            $combo = NormalizeCombo::execute($combo, $normalizeMap);
            $hasStock = $this->getStockCombo->execute((int)$product->getRowId(), $combo);
            $qtyCheck = !($hasStock > 0);
        }
        return [$qtyCheck, $combo];
    }
}
