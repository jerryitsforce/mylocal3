<?php

namespace C4B\FreeProduct\Plugin;

use C4B\FreeProduct\SalesRule\Action\GiftAction;
use Magento\Checkout\Model\Session;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreSwitcherInterface;
use Magento\Quote\Model\Quote;

class UpdateQuoteItemStore
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @param Session $checkoutSession
     */
    public function __construct(
        Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Update store id in active quote after store view switching.
     *
     * @param StoreSwitcherInterface $subject
     * @param callable $proceed
     * @param StoreInterface $fromStore store where we came from
     * @param StoreInterface $targetStore store where to go to
     * @param string $redirectUrl original url requested for redirect after switching
     * @return string url to be redirected after switching
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSwitch(
        StoreSwitcherInterface $subject,
        callable $proceed,
        StoreInterface $fromStore,
        StoreInterface $targetStore,
        string $redirectUrl
    ): string {
        /** @var \Magento\Quote\Model\Quote  */
        $quote = $this->checkoutSession->getQuote();
        /** @var Quote\Item $quoteItem */
        if ($quote && (is_array($quote->getItems()) || is_object($quote->getItems())) && count($quote->getItems())) {
            foreach ($quote->getItems() as $quoteItem) {
                if (($quoteItem->getProductType() == 'giftcard') || ($quoteItem->getOptionByCode(GiftAction::ITEM_OPTION_UNIQUE_ID) instanceof Quote\Item\Option)) {
                    $quoteItem->isDeleted(true);
                    foreach ($quoteItem->getOptions() as $option) {
                        $option->isDeleted(true);
                    }
                }
            }
        }
        return $proceed($fromStore, $targetStore, $redirectUrl);
    }
}