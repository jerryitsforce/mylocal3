<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\CatalogInventory\Plugin\Magento\Bundle\Block\Catalog\Product\View\Type\Bundle;

class Option
{
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->_scopeConfig = $scopeConfig;
    }

    public function afterGetSelectionTitlePrice(
        \Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option $subject,
        $result,
        $selection,
        $includeContainer = true
    ) {
        //Your plugin code
        if($this->isMsgVisible($selection)){
            $str = __('Inventory less than %1 units', $this->getThresholdQty());
            $result .= '<br/><span class="availability only">'.$str.'</span>';
        }
        return $result;
    }

    /**
     * Retrieve current product stock qty
     *
     * @return float
     */
    public function getStockQty($product)
    {
        $qty = 0;
        $productId = $product->getId();
        if ($productId) {
            $qty = $this->getProductStockQty($product);
        }
        return $qty;
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
        $qty = (float)$this->_scopeConfig->getValue(
            \Magento\CatalogInventory\Block\Stockqty\AbstractStockqty::XML_PATH_STOCK_THRESHOLD_QTY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $qty;
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
