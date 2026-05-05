<?php

namespace Branch8\Checkout\Controller\Cart;

use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
class IndividualProduct extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var CustomerCart
     */
    protected $cart;
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @param Session $checkoutSession
     * @param CustomerCart $cart
     * @param JsonFactory $resultJsonFactory
     * @param Context $context
     */
    public function __construct(
        Session $checkoutSession,
        CustomerCart $cart,
        JsonFactory $resultJsonFactory,
        Context $context
    ){
        $this->checkoutSession = $checkoutSession;
        $this->cart = $cart;
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $quoteItemCol = $this->checkoutSession->getQuote()->getItemsCollection();
        foreach($quoteItemCol as $_item){
            $product = $_item->getProduct();
            if($product->getData('individual_product') == 1){
                $this->cart->removeItem($_item->getId())->save();
            }
        }
        return $resultJson->setData([]);
    }
}