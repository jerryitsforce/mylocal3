<?php
namespace Branch8\Checkout\Controller\Cart;

use Branch8\PointMoneyConfig\Helper\Common as PointMoneyConfigCommon;
use Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigFreeRatioRedeemLimitType;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;

class FullPointApply extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Branch8\PointMoneyCollect\Helper\Data
     */
    protected $pointHelperData;
    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    protected $quoteRepository;

    protected $itemCollectionFactory;

    protected $checkoutHelper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Branch8\PointMoneyCollect\Helper\Data $pointHelperData,
        CheckoutSession $checkoutSession,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $itemCollectionFactory,
        \Branch8\Checkout\Helper\Data $checkoutHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->pointHelperData = $pointHelperData;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->checkoutHelper = $checkoutHelper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // $result = $this->resultJsonFactory->create();
        // try {
        //     $quote = $this->checkoutSession->getQuote();
        //     $quote->setTotalsCollectedFlag(false);
        //     $quote->collectTotals();
        //     $this->quoteRepository->save($quote);
        //     $point = $quote->getGrandTotal();
        //     if (($point && is_numeric($point)) || $point == 0) {
        //         if($quote->getGrandTotal() == 0 && $quote->getPointUsedTotal() !== null){
        //             /** Set billing address */
        //             $this->checkoutHelper->setPlaceHolderAddress($quote);
                    
        //             /** Set payment method */
        //             $payment = $quote->getPayment();
        //             $payment->setMethod('free');
        //             $quote->setPayment($payment);
        //             $this->quoteRepository->save($quote);
                    
        //             return $result->setData(['success' => true]);
        //         }
        //         // TODO: validate and apply the point to the current quote
        //         $applyResult = $this->pointHelperData->applyPoint($point, $quote);
        //         $result->setData($applyResult);
        //         if(!$applyResult['success']){
        //             $quoteItemId = $this->getRequest()->getParam('item');
        //             $quoteItem = $this->itemCollectionFactory->create()
        //                 ->addFieldToFilter('item_id', $quoteItemId)
        //                 ->getFirstItem();
        //             if((int)$quoteItem->getData('remain_qty_after_fullpoint_checkout') === null){
        //                 $quoteItem->delete();
        //             }else{
        //                 $quoteItem->setQty((int)$quoteItem->getData('remain_qty_after_fullpoint_checkout'));
        //                 $this->quoteRepository->save($quote);
        //             }
                
        //         }else{
        //             /** Set billing address */
        //             $this->checkoutHelper->setPlaceHolderAddress($quote);
                    
        //             /** Set payment method */
        //             $payment = $quote->getPayment();
        //             $payment->setMethod('free');
        //             $quote->setPayment($payment);
        //             $this->quoteRepository->save($quote);
        //         }
        //     } else {
        //         $quoteItemId = $this->getRequest()->getParam('item');
        //         $quoteItem = $this->itemCollectionFactory->create()
        //             ->addFieldToFilter('item_id', $quoteItemId)
        //             ->getFirstItem();
        //         $quoteItem->delete();
        //         $result->setData(['success' => false, 'msg' => __('Invalid point.')]);
        //     }
        // } catch (\Exception $e) {
        //     $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/custom.log');
        //     $logger = new \Zend_Log();
        //     $logger->addWriter($writer);
        //     $logger->info($e->getMessage());
        //     $result->setData(['success' => false, 'msg' =>__('Something went wrong. Please try again later.')]);
        // }
        // return $result->setData(['success' => false]);
    }


}
