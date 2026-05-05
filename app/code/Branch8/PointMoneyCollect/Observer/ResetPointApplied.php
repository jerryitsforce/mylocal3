<?php

namespace Branch8\PointMoneyCollect\Observer;

use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;
use Magento\Quote\Model\QuoteRepository;

class ResetPointApplied implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;


    protected $quoteRepository;

    protected $quoteItemRepository;

    public function __construct(
        Session $checkoutSession,
        QuoteRepository $quoteRepository,
        QuoteItemRepository $quoteItemRepository
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->quoteItemRepository = $quoteItemRepository;
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        //empty product point applied when visit
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getPointUsedTotal() === null) {
            return;
        }
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
