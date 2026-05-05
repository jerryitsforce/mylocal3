<?php

namespace Branch8\Catalog\Observer;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Downloadable\Model\Product\Type;
use Magento\Framework\Event\ObserverInterface;

class SetSellerForChild implements ObserverInterface{

    protected $b8CatalogHelper;

    protected $_eventManager;

    public function __construct(
        \Branch8\Catalog\Helper\Data $b8CatalogHelper,
        \Magento\Framework\Event\ManagerInterface $_eventManager
    ){
        $this->b8CatalogHelper = $b8CatalogHelper;
        $this->_eventManager = $_eventManager;
    }

    public function execute($observer){
        $product = $observer->getEvent()->getProduct();
        $productType = $product->getTypeId();
//        if(in_array($productType, [
//                \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
//                \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL,
//                \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE,
//                \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD
//            ]
//        )){
//            return;
//        }
        //no need to to for simple, virtual, downloadable, gift card

        //grouped and bundle add existed product, so it will be validated in before save event

        //TODO for configurable

        if($productType == Configurable::TYPE_CODE){
            $controllerEvent = $observer->getEvent()->getController();
            $postData = $controllerEvent->getRequest()->getPostValue();
            $sellerAssign = $postData['product']['assign_seller'];

            $configurableChilds = $product->getTypeInstance()->getUsedProducts($product);
            foreach($configurableChilds as $_configChild){
                $_configChild->setAssignSeller($sellerAssign);
                $this->_eventManager->dispatch(
                    'admin_set_seller_child_product',
                    ['product' => $_configChild]
                );
            }
        }

    }


}