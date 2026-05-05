<?php
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Observer;

use Branch8\OptionsWithStockAndImages\Model\Actions\GetQuoteItemCombo;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Dispatcher for the `checkout_cart_product_add_after` event.
 */
class SetComboForCartQuoteObserver implements ObserverInterface
{
    private GetQuoteItemCombo $getQuoteItemCombo;

    /**
     * @param GetQuoteItemCombo $getQuoteItemCombo
     */
    public function __construct(
        GetQuoteItemCombo $getQuoteItemCombo
    )
    {
        $this->getQuoteItemCombo = $getQuoteItemCombo;
    }

    /**
     * Handle the `checkout_cart_product_add_after` event.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /**
         * @var $product \Magento\Catalog\Model\Product
         * @var $quoteItem \Magento\Quote\Model\Quote\Item
         */
        $quoteItem = $observer->getData('quote_item');
        $combo = $this->getQuoteItemCombo->get($quoteItem);
        $quoteItem->setCombo($combo);
        // TODO: Not yet implemented
    }
}
