<?php

namespace Branch8\GA4\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ProductRepository;

class SalesQuoteRemoveItemObserver implements ObserverInterface
{

    private $helper;
    private $customerSession;
    private $productRepository;
    private $productHelper;
    private $config;

    /**
     *
     */
    public function __construct(
        \Branch8\GA4\Model\Datalayer     $datalayer,
        \Branch8\GA4\Model\Config        $config,
        \Branch8\GA4\Model\ProductHelper $productHelper,
        \Magento\Customer\Model\Session  $customerSession,
        ProductRepository                $productRepository
    )
    {
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->productHelper = $productHelper;
        $this->productRepository = $productRepository;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->isEnabled()) {
            return $this;
        }

        $quoteItem = $observer->getData('quote_item');
        $productId = $quoteItem->getData('product_id');


        //item is being removed from cart when adding to cart got failed
//        if(!empty($quoteItem->getQtyToAdd())) {
//            return $this;
//        }

        if (!$productId) {
            return $this;
        }

        $product = $this->productRepository->getById($productId);
        $qty = $quoteItem->getData('qty');

        /** Need to extend or use another event or plugin to send variant */
        $this->customerSession->setGA4RemoveFromCartData(
            $this->productHelper->removeFromCartPushData($qty, $product, $quoteItem)
        );

        return $this;
    }
}
