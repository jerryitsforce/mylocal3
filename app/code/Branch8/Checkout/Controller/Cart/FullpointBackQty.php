<?php

namespace Branch8\Checkout\Controller\Cart;

class FullpointBackQty extends \Magento\Framework\App\Action\Action{

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    protected $cartItemRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartItemRepositoryInterface $cartItemRepository
    )
    {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->cartItemRepository = $cartItemRepository;
    }

    public function execute()
    {
        $resultJson = $this->_resultJsonFactory->create();
        try{
            $quote = $this->_checkoutSession->getQuote();
            $itemCollections = $quote->getItemsCollection();
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

            
            return $resultJson->setData(['success' => true]);
        }catch(\Exception $e){
            return $resultJson->setData(['success' => false]);
        }
    }
}