<?php
namespace Branch8\Checkout\Controller\Index;

class VirtualFullpointCheckoutConfirmation extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $checkoutSession;

    protected $quoteRepository;

    protected $checkoutHelper;

    protected $pointHelperData;

    protected $itemCollectionFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
       \Magento\Framework\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       \Magento\Checkout\Model\Session $checkoutSession,
       \Magento\Quote\Model\QuoteRepository $quoteRepository,
       \Branch8\Checkout\Helper\Data $checkoutHelper,
       \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $itemCollectionFactory,
       \Branch8\PointMoneyCollect\Helper\Data $pointHelperData
    )
    {
        $this->_pageFactory = $pageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutHelper = $checkoutHelper;
        $this->quoteRepository = $quoteRepository;
        $this->pointHelperData = $pointHelperData;
        $this->itemCollectionFactory = $itemCollectionFactory;
        return parent::__construct($context);
    }
    /**
     * View page action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        $page = $this->_pageFactory->create();

        $quote = $this->checkoutSession->getQuote();
        $allItems = $quote->getAllItems();
        $isFullpointCheckout = false;
        foreach($allItems as $_item){
            $backQty = $_item->getData('remain_qty_after_fullpoint_checkout');
            if($backQty !== null){
                $isFullpointCheckout = true;
                $layout = $page->getLayout();
                $contentBlock = $layout->getBlock('virtual_fullpoint_checkout');
                $contentBlock->setData('quoteItem', $_item);
            }
        }
        
        if(!$isFullpointCheckout){
            $this->checkoutSession->setData('quickCheckout', false);
            return $this->_redirect($this->_redirect->getRefererUrl())->sendResponse();
        }else{
            $this->checkoutSession->setData('quickCheckout', true);
        }

        $point = $quote->getGrandTotal();

        /** Set billing address */
        $this->checkoutHelper->setPlaceHolderAddress($quote);
        // /** Set payment method */
        $payment = $quote->getPayment();
        $payment->setMethod('free');
        $quote->setPayment($payment);

        $quote->setIsGiftOrder(0);
        $quote->setGiftAddressType(0);
        $quote->setGiftAddressFieldsFilled(NULL);

        if($quote->getGrandTotal() == 0 && $quote->getPointUsedTotal() !== null){
            $this->quoteRepository->save($quote);
        }else{
            $applyResult = $this->pointHelperData->applyPoint($point, $quote);
            if(!$applyResult['success']){
                $this->messageManager->addErrorMessage($applyResult['msg']);
                return $this->_redirect($this->_redirect->getRefererUrl())->sendResponse();
            }
        }

        return $page;
    }
}
