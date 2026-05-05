<?php

namespace Branch8\GiftToFriend\Observer;

use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;
use Magento\Quote\Model\QuoteRepository;

class ResetGiftOrder implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Session
     */
    protected $checkoutSession;


    protected $quoteRepository;
    public function __construct(
        Session $checkoutSession,
        QuoteRepository $quoteRepository
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        //reset gift order
        $quote = $this->checkoutSession->getQuote();
        if (!(int)$quote->getIsGiftOrder()) {
            return;
        }
        
        $quote->setIsGiftOrder(0);
        $quote->setGiftAddressType(NULL);
        $quote->setGiftAddressFieldsFilled(NULL);

        $this->quoteRepository->save($quote);
    }

}
