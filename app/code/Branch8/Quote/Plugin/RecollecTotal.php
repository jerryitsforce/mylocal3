<?php

namespace Branch8\Quote\Plugin;

use Magento\Quote\Api\CartRepositoryInterface;

class RecollecTotal
{
    protected $quoteRepository;

    public function __construct(
        CartRepositoryInterface $quoteRepository
    ){
        $this->quoteRepository = $quoteRepository;
    }
    public function beforeGet($subject, $cartId)
    {
        $quote = $this->quoteRepository->getActive($cartId);
        /** !$quote->isVirtual()
         * Only ccollect total if !virtual because the main method is collect for virtual quote.
         */
        if(!$quote->isVirtual()) {
            $quote->collectTotals();
        }
        return [$cartId];
    }
}