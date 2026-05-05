<?php

namespace Branch8\Preorder\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AfterSaveProduct implements ObserverInterface{
    protected $productFactory;
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
    ){
        $this->productFactory = $productFactory;
    }


    /** Check and set pre-order data to child product
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer){
        if($observer->getEvent()->getName() == 'mp_product_save_after'){
            $data = $observer->getEvent()->getData();
            if(!isset($data[0]['id'])){
                return false;
            }
            $pid = $data[0]['id'];
            $product = $this->productFactory->create()->load($pid);
        }else{
            $product = $observer->getEvent()->getProduct();
        }

        if(!in_array($product->getTypeId(), ['bundle', 'grouped', 'configurable'])){
            return ;
        }

        if($product->getTypeId() == 'bundle'){
            $selectionCollection = $product->getTypeInstance(true)
                ->getSelectionsCollection(
                    $product->getTypeInstance(true)->getOptionsIds($product),
                    $product
                );
            foreach($selectionCollection as $_product){
                $this->setChildData($_product, $product);
                $childQty = (int)$_product->getSelectionQty()*1*(int)$product->getWkMppreorderQty();
                if($childQty > 0){
                    $_product->setWkMppreorderQty($childQty);
                }else{
                    $_product->setWkMppreorderQty(null);
                }

                $_product->save();
            }
        }
        /**
         * For any catalog_product events, the grouped product child is not updated on save event,
         * so we need to manually update the pre-order qty(base on option qty))
         */
        if($product->getTypeId() == 'grouped'){
            $groupChilds = $product->getTypeInstance()->getAssociatedProductCollection($product);
            foreach($groupChilds as $_product){
                $this->setChildData($_product, $product);
                $childQty = (int)$_product->getQty() * 1 * (int)$product->getWkMppreorderQty();
                if($childQty > 0){
                    $_product->setWkMppreorderQty($childQty);
                }else{
                    $_product->setWkMppreorderQty(null);
                }

                $_product->save();
            }

        }
        if($product->getTypeId() == 'configurable'){
            $childrens = $product->getTypeInstance()->getUsedProducts($product);
                foreach ($childrens as $_child){
                    $this->setChildData($_child, $product);
                    $childQty = 1*(int)$product->getWkMppreorderQty();
                    if($childQty > 0){
                        $_child->setWkMppreorderQty($childQty);
                    }else{
                        $_child->setWkMppreorderQty(null);
                    }
                    $_child->save();
            }
        }
    }

    /**
     * Set data pre-order from parent product to child, exclude pre-order qty
     * @param $child
     * @param $parent
     * @return void
     */
    protected function setChildData(&$child, $parent){
        $child->setWkMarketplacePreorder($parent->getWkMarketplacePreorder());
        //Mode start/date
        $child->setPreorderMode($parent->getPreorderMode());
        $child->setPreorderStartDate($parent->getPreorderStartDate());
        $child->setPreorderEndDate($parent->getPreorderEndDate());
        $child->setWkMarketplaceAvailability($parent->getWkMarketplaceAvailability());
        $child->setPreorderUseQty($parent->getPreorderUseQty());

        //mode xDays
        $child->setPreorderXDays($parent->getPreorderXDays());
        //Mode Ship date
        $child->setPreorderShipDate($parent->getPreorderShipDate());
    }
}