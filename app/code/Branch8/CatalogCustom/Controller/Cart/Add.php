<?php

namespace Branch8\CatalogCustom\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filter\LocalizedToNormalized;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Webkul\SellerSubAccount\Helper\Data as HelperData;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Quote\Api\Data\AddressInterface;

class Add extends \Magento\Checkout\Controller\Cart\Add
{
    /**
     * @var RequestQuantityProcessor
     */
    private $quantityProcessor;
    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $b8CustomerHelper;
    /**
     * @var HelperData
     */
    protected $subAccountHelper;

    protected $dataObjectProcessor;

    protected $quoteRepository;

    protected $itemCollectionFactory;

    protected $pointHelperData;

    protected $registry;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param \Branch8\Customer\Helper\Data $b8CustomerHelper
     * @param HelperData $subAccountHelper
     * @param RequestQuantityProcessor|null $quantityProcessor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        HelperData $subAccountHelper,
        DataObjectProcessor $dataObjectProcessor,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory $itemCollectionFactory,
        \Branch8\PointMoneyCollect\Helper\Data $pointHelperData,
        \Magento\Framework\Registry $registry,
        ?RequestQuantityProcessor $quantityProcessor = null
    ) {
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager,
            $formKeyValidator, $cart, $productRepository, $quantityProcessor);
        $this->quantityProcessor = $quantityProcessor
            ?? ObjectManager::getInstance()->get(RequestQuantityProcessor::class);
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->subAccountHelper = $subAccountHelper;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->quoteRepository = $quoteRepository;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->pointHelperData = $pointHelperData;
        $this->registry = $registry;
    }
    /**
     * Check if URL corresponds store
     *
     * @param string $url
     * @return bool
     */
    protected function _isInternalUrl($url)
    {
        if (strpos($url, 'http') === false) {
            return false;
        }
        if(strpos($url, 'hotaimember') !== false){
            return true;
        }
        /**
         * Url must start from base secure or base unsecure url
         */
        /** @var $store Store */
        $store = $this->_storeManager->getStore();
        $unsecure = strpos($url, (string) $store->getBaseUrl()) === 0;
        $secure = strpos($url, (string) $store->getBaseUrl(UrlInterface::URL_TYPE_LINK, true)) === 0;
        return $unsecure || $secure;
    }

    public function execute()
    {
        $url = $this->_checkoutSession->getRedirectUrl(true);
        if (!$url) {
            $url = $this->_redirect->getRedirectUrl($this->getCartUrl());
        }
        if(
            $this->b8CustomerHelper->isSeller()
            || $this->b8CustomerHelper->isWaitForSeller()
            || $this->subAccountHelper->isSubAccount()
        ){
            if (!$this->getRequest()->getParam('skipMessage')) {
                $this->messageManager->addErrorMessage(__('Please login as buyer to add product to cart.'));
            }
            return $this->goBack($url);
        }
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            if (!$this->getRequest()->getParam('skipMessage')) {
                $this->messageManager->addErrorMessage(
                    __('Your session has expired')
                );
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
            return $this->goBack($url);
        }

        $params = $this->getRequest()->getParams();
        try {
            if (isset($params['qty'])) {
                $filter = new LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get(
                        \Magento\Framework\Locale\ResolverInterface::class
                    )->getLocale()]
                );
                $params['qty'] = $this->quantityProcessor->prepareQuantity($params['qty']);
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /** Check product availability */
            if (!$product) {
                return $this->goBack();
            }

            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }
            $this->cart->save();

            /**
             * @todo remove wishlist observer \Magento\Wishlist\Observer\AddToCart
             */
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );
            /** Fullpoint quick checkout */
            $fullPointVirtualError = false;
            if($this->getRequest()->getParam('fullpoint_virtual')){

                $quote = $this->_checkoutSession->getQuote();
                $quote->setTotalsCollectedFlag(false);
                /** Remove  coupon */
                $quote->setCouponCode('');

                // $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
                // if($shippingMethod) {
                //     $shippingAddress = $quote->getShippingAddress();
                //     $shippingAddress->unsetData('cached_items_all');
                //     $shippingAddress->unsetData('cached_items_nominal');
                //     $shippingAddress->unsetData('cached_items_nonnominal');
                //     $shippingAddress->setShippingMethod(NULL);
                //     $shippingAddress->setShippingDescription('Reset Shipping on cart page');
                //     $shippingAddress->setShippingAmount(0);
                //     $shippingAddress->setBaseShippingAmount(0);
                //     $quote->setShippingAddress($shippingAddress);
                // }
                $quote->collectTotals();
                // $this->quoteRepository->save($quote);
                $quotePoint = $quote->getGrandTotal();

                $customerPoints = $this->pointHelperData->getHotaiPointByCustomerId($quote->getCustomerId());

                if ($customerPoints < $quotePoint) {
                    $fullPointVirtualError = true;
                    $quoteItemId = $this->registry->registry('fullpoint_item_id');
                    $quoteItem = $this->itemCollectionFactory->create()
                        ->addFieldToFilter('item_id', $quoteItemId)
                        ->getFirstItem();
                    if((int)$quoteItem->getData('remain_qty_after_fullpoint_checkout') === 0){
                        $quoteItem->delete();
                    }else{
                        $quoteItem->setQty((int)$quoteItem->getData('remain_qty_after_fullpoint_checkout'));
                        $this->quoteRepository->save($quote);
                    }

                }
            }
            
            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                if ($this->shouldRedirectToCart()) {
                    if(!$fullPointVirtualError && !$this->getRequest()->getPost('fullpoint_virtual')){
                        $message = __(
                            'You added %1 to your shopping cart.',
                            $product->getName()
                        );
                        $this->messageManager->addSuccessMessage($message);
                    }
                } else {
                    if(!$fullPointVirtualError && !$this->getRequest()->getPost('fullpoint_virtual')){
                        $this->messageManager->addComplexSuccessMessage(
                            'addCartSuccessMessage',
                            [
                                'product_name' => $product->getName(),
                                'cart_url' => $this->getCartUrl(),
                            ]
                        );
                    }
                }
                if ($this->cart->getQuote()->getHasError()) {
                    $errors = $this->cart->getQuote()->getErrors();
                    foreach ($errors as $error) {
                        $this->messageManager->addErrorMessage($error->getText());
                    }
                }
                /** Proccess for returning data */
                if($this->getRequest()->getPost('fullpoint_virtual')){
                    return $this->getFullPointResponse($fullPointVirtualError);
                }else{
                    return $this->goBack(null, $product);
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if (!$this->getRequest()->getParam('skipMessage')) {
                if ($this->_checkoutSession->getUseNotice(true)) {
                    $this->messageManager->addNoticeMessage(
                        $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($e->getMessage())
                    );
                } else {
                    $messages = array_unique(explode("\n", $e->getMessage()));
                    foreach ($messages as $message) {
                        $this->messageManager->addErrorMessage(
                            $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($message)
                        );
                    }
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);
            if (!$url) {
                $url = $this->_redirect->getRedirectUrl($this->getCartUrl());
            }

            return $this->goBack($url);
        } catch (\Exception $e) {
            if (!$this->getRequest()->getParam('skipMessage')) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('We can\'t add this item to your shopping cart right now.')
                );
            }
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_CatalogCustom', 'exceptionlog')){
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
            
            return $this->goBack();
        }

        return $this->getResponse();
    }

    /**
     * Returns cart url
     *
     * @return string
     */
    private function getCartUrl()
    {
        return $this->_url->getUrl('checkout/cart', ['_secure' => true]);
    }

    /**
     * Is redirect should be performed after the product was added to cart.
     *
     * @return bool
     */
    private function shouldRedirectToCart()
    {
        return $this->_scopeConfig->isSetFlag(
            'checkout/cart/redirect_to_cart',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    protected function getFullPointResponse($fullPointVirtualError){
        $result = [
            'fullpoint_virtual' => [
                'success' => !$fullPointVirtualError
            ]
        ];

        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );
        return $this->getResponse();
    }
}
