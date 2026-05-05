<?php

namespace Branch8\Quote\Override\Model\Quote\Address\Total;

use Magento\Quote\Model\Quote\Address\Item as AddressItem;

class Subtotal extends \Magento\Quote\Model\Quote\Address\Total\Subtotal{
    /**
     * @param $address
     * @param $item
     * @return bool
     * @throws \Zend_Log_Exception
     */
    protected function _initItem($address, $item)
    {
        if ($item instanceof AddressItem) {
            $quoteItem = $item->getAddress()->getQuote()->getItemById($item->getQuoteItemId());
        } else {
            $quoteItem = $item;
        }
        $valid = false;
        if ($quoteItem) {

            $product = $quoteItem->getProduct();
            /**
             * Quote super mode flag mean what we work with quote without restriction
             * Ignore check Disable  product
             */
            if ($product /*&& ( $item->getQuote()->getIsSuperMode() || $product->isVisibleInCatalog())*/) {
                $product->setCustomerGroupId($quoteItem->getQuote()->getCustomerGroupId());
                $quoteItem->setConvertedPrice(null);
                $originalPrice = $product->getPrice();
                if ($quoteItem->getParentItem() && $quoteItem->isChildrenCalculated()) {
                    $finalPrice = $quoteItem->getParentItem()->getProduct()->getPriceModel()->getChildFinalPrice(
                        $quoteItem->getParentItem()->getProduct(),
                        $quoteItem->getParentItem()->getQty(),
                        $product,
                        $quoteItem->getQty()
                    );
                    $this->_calculateRowTotal($item, $finalPrice, $originalPrice);
                } elseif (!$quoteItem->getParentItem()) {
                    $finalPrice = $product->getFinalPrice($quoteItem->getQty());
                    $this->_calculateRowTotal($item, $finalPrice, $originalPrice);
                    $this->_addAmount($item->getRowTotal());
                    $this->_addBaseAmount($item->getBaseRowTotal());
                    $address->setTotalQty($address->getTotalQty() + $item->getQty());
                }
                $valid = true;
            }
        }
        return $valid;
    }
}