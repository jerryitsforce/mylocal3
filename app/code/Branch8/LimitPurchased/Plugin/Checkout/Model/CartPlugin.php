<?php
declare(strict_types=1);

namespace Branch8\LimitPurchased\Plugin\Checkout\Model;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Exception\LocalizedException;
use Branch8\LimitPurchased\Model\QtyCondition\LimitPurchasedCondition;
use Magento\Checkout\Model\Session as CheckoutSession;

use Magento\Framework\Message\ManagerInterface;

class CartPlugin
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var LimitPurchasedCondition
     */
    private $limitCondition;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param LimitPurchasedCondition $limitCondition
     * @param CheckoutSession $checkoutSession
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        LimitPurchasedCondition $limitCondition,
        CheckoutSession $checkoutSession,
        ManagerInterface $messageManager
    ) {
        $this->limitCondition = $limitCondition;
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
    }

    /**
     * Intercept updateItems to validate quantities BEFORE they are saved.
     *
     * @param Cart $subject
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    public function beforeUpdateItems(Cart $subject, $data)
    {
        if (empty($data)) {
            return [$data];
        }

        $quote = $this->checkoutSession->getQuote();
        if (!$quote) {
            return [$data];
        }

        // We simulate the new quantities to check limits
        $tempQtys = [];
        foreach ($data as $itemId => $itemInfo) {
            if (isset($itemInfo['qty'])) {
                $tempQtys[$itemId] = (float)$itemInfo['qty'];
            }
        }

        $errorMessages = [];
        $items = $quote->getAllVisibleItems();

        foreach ($items as $item) {
            $itemId = $item->getId();
            if (!isset($tempQtys[$itemId])) {
                continue;
            }

            $newQty = $tempQtys[$itemId];
            $product = $item->getProduct();

            // Run validation
            $result = $this->limitCondition->execute($product->getSku(), 0, $newQty);
            if (!empty($result->getErrors())) {
                foreach ($result->getErrors() as $error) {
                    $errorMessages[] = $error->getMessage();
                }
            }
        }

        if (!empty($errorMessages)) {
            $uniqueErrors = array_unique($errorMessages);
            $finalMessage = implode(', ', $uniqueErrors);
            
            // Add to session for reload persistence
            $this->messageManager->addErrorMessage(__($finalMessage));

            // Prefix with invisible marker for JS AJAX interception
            throw new LocalizedException(__("\xE2\x80\x8B\xE2\x80\x8B\xE2\x80\x8B" . $finalMessage));
        }

        return [$data];
    }
}
