<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAllowProductOOSInCart\Plugin\Magento\Quote\Model;

use Branch8\MarketPlaceParentOrderAllowProductOOSInCart\Model\Config;
use Magento\Framework\Event\Manager;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote;

class QuotePlugin
{
    private \Magento\Framework\DataObject\Factory $objectFactory;
    private Quote\Item\Processor $itemProcessor;
    private Manager $eventManager;
    private Registry $registry;
    private Config $config;

    /**
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     * @param Quote\Item\Processor $itemProcessor
     * @param Manager $eventManager
     * @param Config $config
     * @param Registry $registry
     */
    public function __construct(
        \Magento\Framework\DataObject\Factory     $objectFactory,
        \Magento\Quote\Model\Quote\Item\Processor $itemProcessor,
        Manager                                   $eventManager,
        Config                                    $config,
        Registry                                  $registry
    )
    {
        $this->config = $config;
        $this->registry = $registry;
        $this->eventManager = $eventManager;
        $this->objectFactory = $objectFactory;
        $this->itemProcessor = $itemProcessor;
    }

    /**
     * @param Quote $quote
     * @param callable $process
     * @param \Magento\Catalog\Model\Product $product
     * @param $request
     * @param $processMode
     * @return Quote\Item|string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundAddProduct(
        Quote                          $quote,
        callable                       $process,
        \Magento\Catalog\Model\Product $product,
                                       $request = null,
                                       $processMode = \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_FULL
    )
    {

        $allowOOSProductInCartKey = (bool)$this->registry->registry(Config::ALLOW_REORDER_OOS_PRODUCT_KEY);
        $allowOOSProductInCartConfig = $this->config->allowReorderOOSProduct();
        /**
         * call default function if not config
         */
        if (!$allowOOSProductInCartConfig || !$allowOOSProductInCartKey) {
            return $process($product, $request, $processMode);
        }

        if ($request === null) {
            $request = 1;
        }
        if (is_numeric($request)) {
            $request = $this->objectFactory->create(['qty' => $request]);
        }
        if (!$request instanceof \Magento\Framework\DataObject) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We found an invalid request for adding product to quote.')
            );
        }
        /* if (!$product->isSalable()) {
             throw new \Magento\Framework\Exception\LocalizedException(
                 __('Product that you are trying to add is not available.')
             );
         }*/
        $cartCandidates = $product->getTypeInstance()->prepareForCartAdvanced($request, $product, $processMode);
        /**
         * Error message
         */
        if (is_string($cartCandidates) || $cartCandidates instanceof \Magento\Framework\Phrase) {
            return (string)$cartCandidates;
        }
        /**
         * If prepare process return one object
         */
        if (!is_array($cartCandidates)) {
            $cartCandidates = [$cartCandidates];
        }

        $parentItem = null;
        $errors = [];
        $item = null;
        $items = [];
        foreach ($cartCandidates as $candidate) {
            // Child items can be sticked together only within their parent
            $stickWithinParent = $candidate->getParentProductId() ? $parentItem : null;
            $candidate->setStickWithinParent($stickWithinParent);

            $item = $quote->getItemByProduct($candidate);
            if (!$item) {
                $item = $this->itemProcessor->init($candidate, $request);
                $item->setQuote($quote);
                $item->setOptions($candidate->getCustomOptions());
                $item->setProduct($candidate);
                // Add only item that is not in quote already
                $quote->addItem($item);
            }
            $items[] = $item;

            /**
             * As parent item we should always use the item of first added product
             */
            if (!$parentItem) {
                $parentItem = $item;
            }
            if ($parentItem && $candidate->getParentProductId() && !$item->getParentItem()) {
                $item->setParentItem($parentItem);
            }
            $this->itemProcessor->prepare($item, $request, $candidate);
            // collect errors instead of throwing first one
            if ($item->getHasError()) {
                // no delete item from cart if have error
                //$quote->deleteItem($item);
                foreach ($item->getMessage(false) as $message) {
                    if (!in_array($message, $errors)) {
                        // filter duplicate messages
                        $errors[] = $message;
                    }
                }
                break;
            }
        }
        if (!empty($errors)) {
            throw new \Magento\Framework\Exception\LocalizedException(__(implode("\n", $errors)));
        }
        $this->eventManager->dispatch('sales_quote_product_add_after', ['items' => $items]);
        return $parentItem;
    }
}
