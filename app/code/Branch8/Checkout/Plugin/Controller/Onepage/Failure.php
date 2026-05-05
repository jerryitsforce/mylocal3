<?php
namespace Branch8\Checkout\Plugin\Controller\Onepage;

class Failure
{
    protected $resultPageFactory;

    protected $cartItemRepository;

    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Quote\Api\CartItemRepositoryInterface $cartItemRepository
        ){
        $this->resultPageFactory = $resultPageFactory;
        $this->cartItemRepository = $cartItemRepository;
    }

    public function aroundExecute($subject, $process){
        $checkoutSession = $subject->getOnepage()->getCheckout();
        $lastQuoteId = $checkoutSession->getLastQuoteId();
        $lastOrderId = $checkoutSession->getLastOrderId();
        if($lastQuoteId && $lastOrderId){
            return $process();
        } else if($checkoutSession->getData('quickCheckout')){
            try{
                $quote = $checkoutSession->getQuote();
                if(!$quote->getId()){
                    return $process();
                }
                $itemCollections = $quote->getItemsCollection(false);
                $itemCollections->addFieldToFilter('remain_qty_after_fullpoint_checkout', ['notnull' => true]);
                
                foreach($itemCollections as $_item){
                    $backQty = (int)$_item->getData('remain_qty_after_fullpoint_checkout');
                    if($backQty === 0){
                        $_item->delete();
                    }
                    $_item->setData('remain_qty_after_fullpoint_checkout', null);
                    $_item->setQty($backQty);
                    $this->cartItemRepository->save($_item);
                }
            }catch(\Exception $e){
                
            }
            return $this->resultPageFactory->create();
        }else{
            return $process();
        }
    }
}
