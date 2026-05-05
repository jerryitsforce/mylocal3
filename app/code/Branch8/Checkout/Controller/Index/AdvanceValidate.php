<?php

namespace Branch8\Checkout\Controller\Index;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\QuoteRepository;
use Branch8\WebkulMpsplitorder\Helper\CacheLock as SplitOrderCacheLock;

class AdvanceValidate extends \Magento\Framework\App\Action\Action
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
    protected $checkoutSession;

    protected $quoteRepository;

    /**
     * @var SplitOrderCacheLock
     */
    protected $splitOrderCacheLock;

    public function __construct(
        JsonFactory $resultJsonFactory,
        \Branch8\PointMoneyCollect\Helper\Data $pointHelperData,
        CheckoutSession $checkoutSession,
        QuoteRepository $quoteRepository,
        Context $context,
        SplitOrderCacheLock $splitOrderCacheLock
    ){
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->pointHelperData = $pointHelperData;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->splitOrderCacheLock = $splitOrderCacheLock;
    }



    public function execute(){
        $result = $this->resultJsonFactory->create();
        $request = $this->getRequest();
        $validateData = [];
        //validate point
        $quote = $this->checkoutSession->getQuote();
        $validateData['point']['success'] = true;

        //validate promotion expired
        $clientGrandTotal = $request->getPost('clientGrandTotal');
        $quoteTotal = $quote->getGrandTotal();
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $serverGrandTotal = $quote->getGrandTotal();
        $validateData['grand_total']['success'] = true;

        if((int)$clientGrandTotal != (int)$serverGrandTotal){
            // $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/advance-validate.log');
            // $logger = new \Zend_Log();
            // $logger->addWriter($writer);
            // $logger->info((int)$clientGrandTotal);
            // $logger->info((int)$quoteTotal);
            // $logger->info((int)$serverGrandTotal);

            $validateData['grand_total']['success'] = false;
            //reset point if total change
            $isSplitOrderProcedureLock = $this->splitOrderCacheLock->checkIsSplitOrderProcedureLockNow($quote->getId());
            if (!$isSplitOrderProcedureLock) {
                $this->resetPoint($quote);
            }
        }

//        if(!$this->pointHelperData->isValidPointApply($quote)){
//            $validateData['point']['success'] = false;
//            //reset point if total change
//            $this->resetPoint($quote);
//        }

        $result->setData($validateData);
        return $result;
    }

    protected function resetPoint($quote)
    {
        $quoteItems = $quote->getItemsCollection();
        $quote->setPointUsedTotal(null);
        $quote->setPointDiscountTotal(null);
        foreach($quoteItems as $_qItem){
            $_qItem->setData('row_total_point_used', null);
            $_qItem->setData('row_total_point_discount', null);
        }
        $this->quoteRepository->save($quote);
    }

}