<?php
declare(strict_types=1);

namespace Branch8\QuickEditCartItem\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class AddUpdateHandlesObserver extends \Magetop\Quickview\Observer\AddUpdateHandlesObserver implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|AddUpdateHandlesObserver|false|\Magetop\Quickview\Observer\AddUpdateHandlesObserver|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $layout = $observer->getData('layout');
        $fullActionName = $observer->getData('full_action_name');
        if ($fullActionName != 'checkout_cart_quickConfigure') {
            return $this;
        }
        $itemId = $this->request->getParam('id');
        $productId = $this->request->getParam('product_id');
        if (isset($productId)) {
            try {
                $product = $this->productRepository->getById(
                    $productId,
                    false,
                    $this->storeManager->getStore()->getId()
                );
            } catch (NoSuchEntityException $e) {
                return false;
            }
            $productType = $product->getTypeId();
            $layout->getUpdate()->addHandle('checkout_cart_quickconfigure_type' . $productType);
        }
        $this->quickViewRemove($layout);
        return $this;
    }
}
