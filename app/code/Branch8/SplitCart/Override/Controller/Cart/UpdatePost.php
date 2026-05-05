<?php

namespace Branch8\SplitCart\Override\Controller\Cart;

use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;

class UpdatePost extends \Tigren\SplitCart\Controller\Cart\UpdatePost{
    /**
     * The core function check the shipping address, if there is only virtual quote items are checked
     * The Process to checkout button is disabled. We should allow virtual product(is_vitual quote item))
     * @return Json
     */
    public function execute()
    {
        $status = false;
        $hasError = true;

        try {
            $cartQuote = $this->cart->getQuote();
            $itemIds = $this->getRequest()->getParam('item_ids');
            $itemIds = explode(',', $itemIds);
            $action = $this->getRequest()->getParam('action', 'uncheck');
            $availableToCheckout = $action === 'check' ? 1 : 0;

            foreach ($itemIds as $itemId) {
                if ($itemId) {
                    $this->updateItemAvailableToCheckout((int)$itemId, $cartQuote, $availableToCheckout);
                }
            }

            $this->_eventManager->dispatch('branch8_splitcart_update_post_after', ['quote' => $cartQuote]);

            $shippingMethod = $cartQuote->getShippingAddress()->getShippingMethod();
            if ($shippingMethod) {
                $cartQuote->getShippingAddress()->delete();
                $cartQuote->collectTotals();
            }

            $this->quoteRepository->save($cartQuote);

            $status = true;
            $message = __('Your cart has been updated.');

//            $shippingAddressItems = $cartQuote->getShippingAddress()->getAllItems();
//            $hasError = $cartQuote->getHasError() || !count($shippingAddressItems);
            /**
             * Jerry custom, allow virtual quote item to checkout
             */
            $hasError = $cartQuote->getHasError();

            /**
             * Keep disabled product in cart, so we need validate cart
             */
            //$newQuote = $this->quoteRepository->get($cartQuote->getId());
            $itemCollection = $cartQuote->getItemsCollection();

            $cnt = 0;
            $disabledProduct = [];
            foreach ($itemCollection as $_item) {
                if(!$_item->getAvailableToCheckout()){
                    continue;
                }
                $cnt ++;
                $productItem = $_item->getProduct();
                if($productItem->getStatus() == ProductStatus::STATUS_DISABLED){
                    $disabledProduct[] = $productItem->getSku();
                }
            }
            if(count($disabledProduct) == $cnt){
                $hasError = true;
            }

        } catch (LocalizedException $e) {
            $message = __($e->getMessage());
        } catch (\Exception $e) {
            $message = __('Your cart cannot be updated.');
        }

        $result = [
            'success' => $status,
            'message' => $message,
            'has_error' => !!$hasError
        ];

        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($result);

        return $resultJson;
    }

    /**
     * Updates item qty for the specified cart
     * Do not allow setAvailableToCheckout for disable product
     *
     * @param int $itemId
     * @param Quote $cart
     * @param int $availableToCheckout
     * @throws LocalizedException
     */
    private function updateItemAvailableToCheckout(int $itemId, Quote $cart, int $availableToCheckout)
    {
        $cartItem = $cart->getItemById($itemId);
        if ($cartItem) {
            $product = $cartItem->getProduct();
            if($product->getStatus() == ProductStatus::STATUS_DISABLED){
                throw new LocalizedException(__($product->getName().' is disabled.'));
            }
            $cartItem->setAvailableToCheckout($availableToCheckout);
        }
    }
}
