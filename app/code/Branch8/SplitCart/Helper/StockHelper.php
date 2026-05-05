<?php

namespace Branch8\SplitCart\Helper;


use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Model\Quote\Item\AbstractItem;

class StockHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected StockRegistryInterface $stockRegistry;

    /**
     * @param Context $context
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(Context $context, StockRegistryInterface $stockRegistry)
    {
        $this->stockRegistry = $stockRegistry;
        parent::__construct($context);
    }

    /**
     * @param AbstractItem $item
     * @return int
     */
    public function isItemProducInStock($item)
    {
        $product = $item->getProduct();
        $product->getIsSalable();
        $stockItem = $product->getExtensionAttributes()?->getStockItem();
        $stockItem = $stockItem ?? $this->stockRegistry->getStockItem($product->getId());

        if(!filter_var($stockItem->getManageStock(), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        return $stockItem->getIsInStock();
    }
}
