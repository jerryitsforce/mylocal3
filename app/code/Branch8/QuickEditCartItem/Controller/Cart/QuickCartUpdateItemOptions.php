<?php

namespace Branch8\QuickEditCartItem\Controller\Cart;

use Branch8\SplitCart\Model\Services\BuildCartItemData;
use Magento\Checkout\Helper\Cart as CartHelper;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\LocalizedToNormalized;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;

/**
 * Process updating product options in a cart item.
 */
class QuickCartUpdateItemOptions extends \Magento\Checkout\Controller\Cart\UpdateItemOptions
{
    private $buildCartItemData;
    private $virtualProductHelper;
    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param CustomerCart $cart
     */
    public function __construct(
        Context               $context,
        ScopeConfigInterface  $scopeConfig,
        Session               $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator             $formKeyValidator,
        CustomerCart          $cart,
        BuildCartItemData     $buildCartItemData,
        VirtualProductHelper  $virtualProductHelper
    )
    {
        $this->buildCartItemData = $buildCartItemData;
        $this->virtualProductHelper = $virtualProductHelper;
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
    }

    /**
     * Update product configuration for a cart item.
     *
     * @return Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $params = $this->getRequest()->getParams();
        $cartUrl = $this->_objectManager->get(CartHelper::class)->getCartUrl();
        if (!isset($params['options'])) {
            $params['options'] = [];
        }
        try {
            if (isset($params['qty'])) {
                $inputFilter = new LocalizedToNormalized(
                    [
                        'locale' => $this->_objectManager->get(ResolverInterface::class)->getLocale(),
                    ]
                );
                $params['qty'] = $inputFilter->filter($params['qty']);
            }

            $quoteItem = $this->cart->getQuote()->getItemById($id);
            if (!$quoteItem) {
                throw new LocalizedException(
                    __("The quote item isn't found. Verify the item and try again.")
                );
            }

            $item = $this->cart->updateItem($id, new DataObject($params));

            if ($this->virtualProductHelper->isBatchImportTicketProduct($item->getProductId())) {
                $checkResultArray = $this->virtualProductHelper->checkIfQuantityEnoughByCustomOptionAndRequestQuantity(
                    $item->getProductId(),
                    $this->virtualProductHelper->getBatchSettingCustomOptionValueForQuoteItemFlow($item)->getTitle(),
                    $params['qty']
                );

                $isQuantityEnough = $checkResultArray['result'] ?? false;
                $reason = $checkResultArray['reason'] ?? null;

                if (!$isQuantityEnough) {
                    throw new LocalizedException(__("The requested quantity is not available. Please adjust the purchase quantity."));
                }
            }

            if (is_string($item)) {
                throw new LocalizedException(__($item));
            }
            if ($item->getHasError()) {
                throw new LocalizedException(__($item->getMessage()));
            }

            $related = $this->getRequest()->getParam('related_product');
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }
            $this->cart->save();
            $this->_eventManager->dispatch(
                'checkout_cart_update_item_complete',
                ['item' => $item, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );
            $url = $this->_checkoutSession->getRedirectUrl(true);
            if (!$url) {
                $url = $this->_redirect->getRedirectUrl($cartUrl);
            }
            $this->messageManager->addNoticeMessage(__(
                'Update successfully'
            ));
            return $this->buildResponde(true, $item);
        } catch (LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage($e->getMessage());
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addErrorMessage($message);
                }
            }
            return $this->buildResponde(false, null);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t update the item right now.'));
            $this->_objectManager->get(LoggerInterface::class)->critical($e);
            return $this->buildResponde(false, null);
        }
        return $this->resultRedirectFactory->create()->setPath('*/*');
    }

    /**
     * @param $success
     * @param Item|null $item
     * @return \Magento\Framework\App\ResponseInterface
     */
    protected function buildResponde($success = true, Item $item = null)
    {
        $result = [
            'backUrl' => $this->_objectManager->get(CartHelper::class)->getCartUrl(),
            'status' => $success,
            'closeMagnificPopup' => $success? true : false,
            'reloadData' => $success ? $this->buildCartItemData->build([$item]) : []
        ];
        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)
                ->jsonEncode($result)
        );

        return $this->getResponse();
    }
}
