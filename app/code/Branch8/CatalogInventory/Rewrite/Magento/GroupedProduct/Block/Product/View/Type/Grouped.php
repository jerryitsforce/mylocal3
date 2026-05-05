<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\CatalogInventory\Rewrite\Magento\GroupedProduct\Block\Product\View\Type;

class Grouped extends \Magento\GroupedProduct\Block\Product\View\Type\Grouped
{
    /**
     * Retrieve current product stock qty
     *
     * @return float
     */
    public function getStockQty($product)
    {
        if (!$this->hasData('product_stock_qty_' . $product->getId())) {
            $qty = 0;
            $productId = $product->getId();
            if ($productId) {
                $qty = $this->getProductStockQty($product);
            }
            $this->setData('product_stock_qty_' . $product->getId(), $qty);
        }
        return $this->getData('product_stock_qty_' . $product->getId());
    }

    /**
     * Retrieve product stock qty
     *
     * @param Product $product
     * @return float
     */
    public function getProductStockQty($product)
    {
        return $this->stockRegistry->getStockStatus($product->getId(), $product->getStore()->getWebsiteId())->getQty();
    }

    /**
     * Retrieve threshold of qty to display stock qty message
     *
     * @return string
     */
    public function getThresholdQty()
    {
        if (!$this->hasData('threshold_qty')) {
            $qty = (float)$this->_scopeConfig->getValue(
                \Magento\CatalogInventory\Block\Stockqty\AbstractStockqty::XML_PATH_STOCK_THRESHOLD_QTY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $this->setData('threshold_qty', $qty);
        }
        return $this->getData('threshold_qty');
    }

    /**
     * Retrieve visibility of stock qty message
     *
     * @return bool
     */
    public function isMsgVisible($product)
    {
        return $this->getStockQty($product) > 0
            && $this->getStockQtyLeft($product) > 0
            && $this->getStockQtyLeft($product) < $this->getThresholdQty();
    }

    /**
     * Retrieve current product qty left in stock
     *
     * @return float
     */
    public function getStockQtyLeft($product)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId());
        $minStockQty = $stockItem->getMinQty();
        return $this->getStockQty($product) - $minStockQty;
    }
}