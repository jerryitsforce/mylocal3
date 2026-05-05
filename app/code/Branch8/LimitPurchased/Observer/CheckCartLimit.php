<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\LimitPurchased\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Branch8\LimitPurchased\Model\QtyCondition\LimitPurchasedCondition;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class CheckCartLimit implements ObserverInterface
{
    public static $isExecuting = false;

    /**
     * @var LimitPurchasedCondition
     */
    private $limitPurchasedCondition;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;

    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    private $actionFlag;

    /**
     * @param LimitPurchasedCondition $limitPurchasedCondition
     * @param CheckoutSession $checkoutSession
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\App\ActionFlag $actionFlag
     */
    public function __construct(
        LimitPurchasedCondition $limitPurchasedCondition,
        CheckoutSession $checkoutSession,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\ActionFlag $actionFlag
    ) {
        $this->limitPurchasedCondition = $limitPurchasedCondition;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->actionFlag = $actionFlag;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $eventName = $observer->getEvent()->getName();

        // ------------------------------------------------------------------------------------------------ //
        // 1. Intercept `updateItemQty` AJAX action BEFORE Magento processes it natively
        //    because Magento core skips MSI (and thus our limits) entirely during this API call!
        // ------------------------------------------------------------------------------------------------ //
        if ($eventName === 'controller_action_predispatch_checkout_cart_updateitemqty') {
            $cartData = $this->request->getParam('cart');
            $hasError = false;
            $responseErrors = [];
            if (is_array($cartData)) {
                self::$isExecuting = true;
                try {
                    $quote = $this->checkoutSession->getQuote();
                    foreach ($cartData as $itemId => $itemInfo) {
                        $item = $quote->getItemById($itemId);
                        if ($item && isset($itemInfo['qty'])) {
                            $qty = (float)$itemInfo['qty'];
                            $pid = (string)$item->getProductId();
                            
                            $result = $this->limitPurchasedCondition->execute($pid, 0, $qty);
                            if (!empty($result->getErrors())) {
                                $hasError = true;
                                foreach ($result->getErrors() as $error) {
                                    // The frontend expects array of objects in error_message JSON string
                                    $responseErrors[] = [
                                        'error' => (string)$error->getMessage(),
                                        'itemId' => $itemId
                                    ];
                                }
                            }
                        }
                    }
                } finally {
                    self::$isExecuting = false;
                }
            }

            if ($hasError) {
                // Prevent core Controller execute()
                $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);

                // Inject our Custom JSON Error to matching Magento's native UpdateItemQty format
                $controller = $observer->getControllerAction();
                $response = $controller->getResponse();

                // Prefix error_message with invisible marker so update-shopping-cart-mixin.js
                // detects it and shows the popup instead of a generic alert
                $markedErrors = [];
                foreach ($responseErrors as $err) {
                    $markedErrors[] = [
                        'error' => "\xE2\x80\x8B\xE2\x80\x8B\xE2\x80\x8B" . $err['error'],
                        'itemId' => $err['itemId']
                    ];
                }

                $jsonResult = [
                    'success' => false,
                    'error_message' => json_encode($markedErrors)
                ];
                $response->representJson(json_encode($jsonResult));
            }
            return;
        }

        // ------------------------------------------------------------------------------------------------ //
        // 2. Standard Cart Observation
        // ------------------------------------------------------------------------------------------------ //
        self::$isExecuting = true;
        try {
            $quote = $this->checkoutSession->getQuote();
            if (!$quote || !$quote->getItemsCount()) {
                return;
            }

        $aggregatedQtys = [];
        $selectedOnlyQtys = [];
        $productTypeMap = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $pid = (string)$item->getProductId();
            $qty = (float)$item->getQty();
            $productTypeMap[$pid] = $item->getProductType();
            
            if (!isset($aggregatedQtys[$pid])) {
                $aggregatedQtys[$pid] = 0.0;
                $selectedOnlyQtys[$pid] = 0.0;
            }
            
            $aggregatedQtys[$pid] += $qty;
            if ($item->getAvailableToCheckout()) {
                $selectedOnlyQtys[$pid] += $qty;
            }
        }

        $hasError = false;
        $allErrorMessages = [];
        $blockingErrorMessages = [];

        $eventName = $observer->getEvent()->getName();
        $eventItem = $observer->getEvent()->getQuoteItem();
        $isUpdate = in_array($eventName, [
            'checkout_cart_update_items_after', 
            'checkout_cart_product_add_after', 
            'branch8_splitcart_update_post_after'
        ]);

        foreach ($quote->getAllVisibleItems() as $item) {
            $pid = (string)$item->getProductId();
            $type = $item->getProductType();
            
            $isProductWithOptions = ($type === 'configurable' || $item->getHasOptions());
            $qtyToCheck = $isProductWithOptions ? $aggregatedQtys[$pid] : $selectedOnlyQtys[$pid];
            
            // If the item itself is already checked and over limit, or if the product's relevant qty is over limit
            $result = $this->limitPurchasedCondition->execute(
                $pid,
                0,
                $qtyToCheck
            );

            // Special Case: If the item is unchecked, but its OWN qty alone exceeds the limit, 
            // we should still show an error on it so the user knows why.
            if (!$isProductWithOptions && !$item->getAvailableToCheckout() && empty($result->getErrors())) {
                $ownResult = $this->limitPurchasedCondition->execute($pid, 0, (float)$item->getQty());
                if (!empty($ownResult->getErrors())) {
                    $result = $ownResult;
                }
            }

            if (!empty($result->getErrors())) {
                $hasError = true;
                $item->setHasError(true);
                // We use additional_data column on the Quote Item to store persistent flag safely
                $ad = $item->getAdditionalData();
                $limitVal = -1;
                foreach ($result->getErrors() as $error) {
                    if (preg_match('/(\d+)/', (string)$error->getMessage(), $matches)) {
                        $limitVal = (int)$matches[1];
                        break;
                    }
                }
                $marker = $limitVal>=0 ? "limit_error|qty:{$limitVal}" : "limit_error";
                // Remove old limit_error marker if present, then re-add with updated qty
                $cleanAd = preg_replace('/,?limit_error(\|qty:\d+)?/', '', (string)$ad);
                $cleanAd = trim($cleanAd, ',');
                $item->setAdditionalData($cleanAd ? $cleanAd . ',' . $marker : $marker);
                
                $messages = [];
                // Ensure invisible marker is at the beginning of the messages for easy detection
                $messages[] = "\xE2\x80\x8B\xE2\x80\x8B\xE2\x80\x8B";
                foreach ($result->getErrors() as $error) {
                    $messages[] = (string)$error->getMessage();
                    $allErrorMessages[] = (string)$error->getMessage();

                    // Only block (throw exception) if the item's qty was changed in this request 
                    // or it is the specific item being added in 'product_add_after'
                    // or the user just ticked the SplitCart checkbox
                    $isQtyModified = ($item->getOrigData('qty') === null || (float)$item->getQty() != (float)$item->getOrigData('qty'));
                    $isAddedItem = ($eventItem && $item->getId() == $eventItem->getId());
                    $isJustChecked = ($eventName === 'branch8_splitcart_update_post_after' && $item->getAvailableToCheckout());

                    if ($isUpdate && ($isQtyModified || $isAddedItem || $isJustChecked)) {
                        $blockingErrorMessages[] = (string)$error->getMessage();
                    }
                }
                $item->setMessage(implode('', $messages));
                
                // Mark quote as having error to block proceeding ONLY if the violating item is checked
                if ($item->getAvailableToCheckout()) {
                    $quote->setHasError(true);
                }

                // Revert invalid quantities to avoid them being saved inadvertently
                if ($isUpdate) {
                    // Only revert if it was modified. If it's a pre-existing error, let it stay shown
                    if ($item->getOrigData('qty') !== null && $item->getQty() != $item->getOrigData('qty')) {
                        $item->setQty($item->getOrigData('qty'));
                    }
                }
            } else {
                // Remove limit_error from additional_data flag and message
                $ad = $item->getAdditionalData();
                if (strpos((string)$ad, 'limit_error') !== false) {
                    $newAd = preg_replace('/limit_error(\|qty:\d+)?/', '', (string)$ad);
                    $newAd = trim(str_replace(',,', ',', $newAd), ',');
                    $item->setAdditionalData($newAd);

                    // Since we just cleared the limit error, we can aggressively empty the message if there were no other things
                    $item->setMessage('');
                    $item->setHasError(false);

                    // Explicitly remove from error_infos to ensure default.phtml doesn't see it
                    if (method_exists($item, 'removeErrorInfo')) {
                        $item->removeErrorInfo('limit-purchased');
                        $item->removeErrorInfo('is_correct_qty-limit_purchased');
                    }

                    // AUTO-RECHECK: If it was previously disabled/unchecked due to limit error, re-enable it for the user
                    // Only do this on QTY Update, not on explicit checkbox toggle
                    if ($eventName === 'checkout_cart_update_items_after' && !$item->getAvailableToCheckout()) {
                        $item->setAvailableToCheckout(1);
                    }
                }
            }
        }

            if ($isUpdate && !empty($blockingErrorMessages)) {
                 // Extract unique errors to avoid duplicate messages for the same product
                 $uniqueErrors = array_unique($blockingErrorMessages);
                 $errorText = implode(', ', $uniqueErrors);
                 
                 // The Exception will be caught by UpdatePost.php and returned as JSON {message: "...", has_error: true}
                 // The JS in splitCart.js reads res.message and shows the popup directly — no messageManager needed

                 throw new \Magento\Framework\Exception\LocalizedException(__($errorText));
            }
        } finally {
            self::$isExecuting = false;
        }
    }
}
