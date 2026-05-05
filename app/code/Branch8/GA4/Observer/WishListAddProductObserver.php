<?php

namespace Branch8\GA4\Observer;

use Branch8\GA4\Model\ProductHelper;
use Magento\Framework\Event\ObserverInterface;

class WishListAddProductObserver implements ObserverInterface
{
    private $config;
    private $datalayer;
    private $customerSession;
    private $productHelper;

    /**
     * @param \Branch8\GA4\Model\Datalayer $datalayer
     * @param \Branch8\GA4\Model\Config $config
     * @param ProductHelper $productHelper
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Branch8\GA4\Model\Datalayer    $datalayer,
        \Branch8\GA4\Model\Config       $config,
        ProductHelper                   $productHelper,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->productHelper = $productHelper;
        $this->config = $config;
        $this->datalayer = $datalayer;
        $this->customerSession = $customerSession;
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

        $product = $observer->getData('product');
        $wishlistItem = $observer->getData('item');
        $buyRequest = $wishlistItem->getBuyRequest()->getData();
        $this->customerSession->setGA4AddToWishListData(
            $this->productHelper->addToWishListPushData($product, $buyRequest, $wishlistItem)
        );

        return $this;
    }

}
