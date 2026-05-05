<?php

namespace Branch8\Checkout\Controller\Cart;

class ReloadItems extends \Magento\Framework\App\Action\Action{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    protected $_quoteItemCollectionFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $quoteCollectionFactory
    )
    {
        parent::__construct($context);
        $this->_pageFactory = $pageFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_quoteItemCollectionFactory = $quoteCollectionFactory;
    }

    public function execute()
    {
        $page = $this->_pageFactory->create();
        $layout = $page->getLayout();
        try {
            $quoteId = $this->_checkoutSession->getQuoteId();
            $quoteItems = $this->_quoteItemCollectionFactory->create()
                ->addFieldToFilter('quote_id', $quoteId)
                ->addFieldToSelect('item_id');
            if ($quoteItems->getSize()) {
                $itemsBlock = $layout->getBlock('checkout.cart.form');
                $returnData = [
                    'success' => true,
                    'hasItem' => true,
                    'items' => $itemsBlock->toHtml()
                ];
            } else {
                $noItemsBlock = $layout->renderElement('checkout.cart.noitems');
                $returnData = [
                    'success' => true,
                    'hasItem' => false,
                    'noItemHtml' => $noItemsBlock
                ];
            }
        }catch(\Exception $e){
            $returnData = [
                'success' => false
            ];
        }

        $resultJson = $this->_resultJsonFactory->create();
        return $resultJson->setData($returnData);
    }
}