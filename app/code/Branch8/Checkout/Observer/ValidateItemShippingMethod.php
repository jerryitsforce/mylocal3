<?php

namespace Branch8\Checkout\Observer;

use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;

class ValidateItemShippingMethod implements \Magento\Framework\Event\ObserverInterface
{
    protected $quoteItemRepository;
    public function __construct(
        QuoteItemRepository $quoteItemRepository
    )
    {
        $this->quoteItemRepository = $quoteItemRepository;
    }

    public function execute($observer){
        $request = $observer->getEvent()->getRequest();
        if(!$request->getParam('available_to_checkout')){
            return;
        }

        $intersecMethod = [];
        $item = $observer->getEvent()->getItem();
        if($item->getIsVirtual()){
            $item->setAvailableToCheckout($request->getParam('available_to_checkout'));
            $item->save();
            return;
        }else{
            $item->setAvailableToCheckout($request->getParam('available_to_checkout'));
            $productType = $item->getProductType();
            if($productType == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
                $children = $item->getChildren();
                $childProduct = $children[0]->getProduct();
                $shippingMethods = $childProduct->getShippingMethod();
                $productShippingMethods = explode(',', (string)$shippingMethods);
                $intersecMethod = $productShippingMethods;

            }else{
                $intersecMethod = explode(',', (string)$item->getProduct()->getShippingMethod());
            }
            $quoteItemCollection = $item->getQuote()->getItemsCollection(false)
                ->addFieldToFilter('available_to_checkout', 1)
                ->addFieldToFilter('is_virtual', 0)
                ->addFieldToFilter('item_id', ['neq' => $item->getId()]);
            if(!$quoteItemCollection->getSize()){
                $item->setData('available_to_checkout', $request->getParam('available_to_checkout'));
                $item->save();
                return;
            }
            foreach($quoteItemCollection as $_item){
                $_itemProduct = $_item->getProduct();
                $_itemShippingMethod = explode(',', (string)$_itemProduct->getShippingMethod());
                $intersecMethod = array_intersect($intersecMethod, $_itemShippingMethod);
            }
            if(!empty($intersecMethod)){
                $item->setAvailableToCheckout($request->getParam('available_to_checkout'));
                $item->save();
            }
        }

    }
}