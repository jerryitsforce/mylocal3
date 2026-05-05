<?php

namespace Branch8\Preorder\Observer;

use Magento\Framework\Event\ObserverInterface;

class DeductQty implements ObserverInterface{
    /**
     * @var \Webkul\MarketplacePreorder\Helper\Data
     */
    protected $wkPreorderHelper;
    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $productAction;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Webkul\MarketplacePreorder\Helper\Data $wkPreorderHelper
     * @param \Magento\Catalog\Model\Product\Action $productAction
     * @param \Magento\Catalog\Model\ProductFactory $product
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Webkul\MarketplacePreorder\Helper\Data $wkPreorderHelper,
        \Magento\Catalog\Model\Product\Action $productAction,
        \Magento\Catalog\Model\ProductFactory $product,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ){
        $this->wkPreorderHelper = $wkPreorderHelper;
        $this->productAction = $productAction;
        $this->_productFactory = $product;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer){//return;
        $order = $observer->getEvent()->getOrder();
        $orderItems = $order->getAllItems();
        foreach($orderItems as $_item){
            if($_item->getParentId()){
                continue;
            }
            $productId = $_item->getProductId();
            $product = $this->_productFactory->create()->load($productId);
            $preorderQty = $product->getWkMppreorderQty();
            if($this->wkPreorderHelper->isPreorder($productId) &&
                $product->getPreorderMode() == \Branch8\Preorder\Model\Source\PreorderMode::START_END_DATE
            && $product->getPreorderUseQty()){

                $this->productAction->updateAttributes(
                    [$productId],
                    [
                        'wk_mppreorder_qty' => max(0, (int) $preorderQty - (int) $_item->getQtyOrdered())
                    ],
                    $this->_storeManager->getStore()->getId()
                );
            }
        }
    }

}