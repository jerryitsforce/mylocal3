<?php

namespace Branch8\GA4\Plugin;

use Branch8\GA4\Model\ProductHelper;

class WishlistAddToCart
{
    private $datalayer;
    private $_checkoutSession;
    private $config;
    private $productHelper;

    public function __construct(
        \Branch8\GA4\Model\Datalayer    $datalayer,
        \Branch8\GA4\Model\Config       $config,
        ProductHelper                   $productHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->config = $config;
        $this->productHelper = $productHelper;
        $this->datalayer = $datalayer;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Wishlist\Model\Item $subject
     * @param $result
     * @return bool
     * @throws \Magento\Catalog\Model\Product\Exception
     */
    public function afterAddToCart(
        \Magento\Wishlist\Model\Item $subject,
                                     $result
    )
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        if ($result) {
            $buyRequest = $subject->getBuyRequest();
            $qty = $buyRequest->getData('qty');
            $product = $subject->getProduct();

            /** multiple products can be added at once, so they are merged */
            $currentAddToCartData = $this->_checkoutSession->getGA4AddToCartData();
            $addToCartPushData = $this->productHelper->addToCartPushData(
                $qty,
                $product,
                $buyRequest,
                true
            );
            $newAddToCartPushData = $this->productHelper->mergeAddToCartPushData(
                $currentAddToCartData,
                $addToCartPushData
            );
            $this->_checkoutSession->setGA4AddToCartData(null);
            $this->_checkoutSession->unsGA4AddToCartData();
            $this->_checkoutSession->setGA4AddToCartData($newAddToCartPushData);
        }
        return $result;
    }


}
