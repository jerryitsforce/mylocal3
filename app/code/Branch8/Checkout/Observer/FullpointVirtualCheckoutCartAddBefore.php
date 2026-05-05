<?php
namespace Branch8\Checkout\Observer;
use Magento\Framework\Exception\LocalizedException;

class FullpointVirtualCheckoutCartAddBefore implements \Magento\Framework\Event\ObserverInterface
{
    protected $checkoutSession;

    protected $fullpointCheckoutHelper;

    protected $messageManager;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Branch8\Checkout\Helper\FullpointCheckout $fullpointCheckoutHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->fullpointCheckoutHelper = $fullpointCheckoutHelper;
        $this->messageManager = $messageManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getData('product');
        $requestInfor = $observer->getData('info');

        $isValidData = $this->fullpointCheckoutHelper->isValidFullPointCheckout($product);
        if(!isset($requestInfor['fullpoint_virtual']) || $requestInfor['fullpoint_virtual'] == 0){
            return;
        }
        
        if(isset($requestInfor['fullpoint_virtual']) && $requestInfor['fullpoint_virtual'] == 1 && !$isValidData['result']){
            if($isValidData['data']['is_logged_in']){
                $this->messageManager->addErrorMessage(__('Please login to buy this product.'));
                throw new LocalizedException(__('Please login to buy this product.'));
            }else{
                $this->messageManager->addErrorMessage(__('Please login to buy this product.'));
                throw new LocalizedException(__('This product is not eligible for quick purchase.'));
            }
            
        }
        
        $quote = $this->checkoutSession->getQuote();  
        if ($quote->getPointUsedTotal() !== null) {
            $quote->setPointUsedTotal(null);
            $quote->setPointDiscountTotal(null);
        }
        $allItems = $quote->getAllItems();
        $diffCnt = 0;
        if(count($allItems)){
            foreach($allItems as $_item){
                $_item->setAvailableToCheckout(0);
                $itemProductId = $_item->getProductId();
                if($itemProductId != $product->getId()){
                    $diffCnt ++;
                    if($_item->getData('remain_qty_after_fullpoint_checkout') !== null){
                        $_item->setData('remain_qty_after_fullpoint_checkout', null);
                    }
                    continue;
                }

                $buyInforRequest = $_item->getBuyRequest()->getData();
                if(is_array($buyInforRequest) && isset($buyInforRequest['options']) && is_array($buyInforRequest['options'])){
                    $optionsInDB = $buyInforRequest['options'];
                    if($optionsInDB == $requestInfor['options']){
                        $oldQtyInCart = (int)$_item->getQty();
                        $_item->delete();
                        $product->setOldQtyInCart($oldQtyInCart);
                    }else{
                        $product->setOldQtyInCart(0);
                    }
                }else{
                    $oldQtyInCart = (int)$_item->getQty();
                    $_item->delete();
                    $product->setOldQtyInCart($oldQtyInCart);
                }
            }
            if($diffCnt == count($allItems)){
                $product->setOldQtyInCart(0);
            }
        }else{
            $product->setOldQtyInCart(0);
        }

    }
}