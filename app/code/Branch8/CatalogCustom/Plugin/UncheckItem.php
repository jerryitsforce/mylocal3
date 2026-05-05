<?php

namespace Branch8\CatalogCustom\Plugin;

use Magento\Checkout\Controller\Index\Index;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Controller\ResultInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

class UncheckItem
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    protected $cart;
    protected $quoteRepository;

    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        Cart $cart,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->cart = $cart;
        $this->productRepository = $productRepository;
    }

    /**
     * Custom logic unchecked another product when product with individual_product exist in cart
     * @param Index $subject
     * @return array
     */
    public function beforeExecute(Index $subject): array
    {
        $allItems = $this->cart->getQuote()->getAllItems();
        $itemUncheck = [];

        // Check if there is at least one item with individual_product = 1
        $hasIndividualProduct = false;

        foreach ($allItems as $item) {
            if ($item->getParentItemId()) {
                continue;
            }

            try {
                $product = $item->getProduct();

                if ($product->getData('individual_product')) {
                    $hasIndividualProduct = true;
                    break; // No need to check further once we find an item with individual_product = 1
                }
            } catch (\Exception $exception) {
                return [];
            }
        }

        // If there is at least one item with individual_product = 1, proceed to update $itemUncheck array
        if ($hasIndividualProduct) {
            foreach ($allItems as $item) {
                if ($item->getParentItemId()) {
                    continue;
                }

                try {
                    $product = $item->getProduct();

                    if (!$product->getData('individual_product')) {
                        $itemUncheck[] = $item->getId();
                    }
                } catch (\Exception $exception) {
                    return [];
                }
            }

            if (!empty($itemUncheck)) {
                $cartQuote = $this->cart->getQuote();

                foreach ($itemUncheck as $itemId) {
                    $this->updateItemAvailableToCheckout($itemId, $cartQuote, 0);
                }

                $this->quoteRepository->save($cartQuote);
            }
        }

        return [];
    }

    /**
     * Updates item qty for the specified cart
     *
     * @param int $itemId
     * @param Quote $cart
     * @param int $availableToCheckout
     */
    private function updateItemAvailableToCheckout(int $itemId, Quote $cart, int $availableToCheckout)
    {
        $cartItem = $cart->getItemById($itemId);
        if ($cartItem) {
            $cartItem->setAvailableToCheckout($availableToCheckout);
        }
    }

}
