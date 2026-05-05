<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Branch8\Checkout\Observer\Controller;
use Branch8\HotaiShipping\Helper\Data as HotaiShippingHelper;
use Magento\Quote\Api\CartItemRepositoryInterface as QuoteItemRepository;
use Magento\Quote\Model\QuoteRepository;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Framework\Session\SessionManager;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use Carbon\Carbon;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ActionFlag;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\Checkout\Model\Session;
use \Branch8\SplitCart\Helper\Data;
use Magento\Downloadable\Model\Product\Type;
use Webkul\SellerSubAccount\Helper\Data as HelperData;

class ActionPredispatchCheckoutIndexIndex implements \Magento\Framework\Event\ObserverInterface
{

    /** @var \Magento\Framework\Session\SessionManager $sessionManager */
    protected $sessionManager;

    /** @var \Branch8\HotaiAuth\Service\HotaiAuthService $hotaiAuthService */
    protected $hotaiAuthService;

    /** @var \Magento\Framework\UrlInterface $_urlInterface */
    protected $_urlInterface;

    /** @var \Magento\Framework\App\ActionFlag $actionFlag */
    protected $actionFlag;

    /** @var \Magento\Framework\Message\ManagerInterface $_messageManager */
    protected $_messageManager;

    /** @var \Magento\Checkout\Model\Session */
    protected $checkoutSession;

    /** @var \Branch8\SplitCart\Helper\Data $splitCartHelper */
    protected $splitCartHelper;

    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $b8CustomerHelper;

    /** @var \Branch8\HotaiShipping\Helper\Data $hotaiShippingHelper*/
    protected $hotaiShippingHelper;
    /**
     * @var MarketplaceHelper
     */
    protected $marketPlaceDataHelper;
    /**
     * @var array
     */
    protected $productCartItem = [];

    protected $customerSession;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var HelperData
     */
    protected $subAccountHelper;
    /**
     * @var \Branch8\Checkout\Helper\Data
     */
    protected $b8CheckoutHelper;

    protected $quoteRepository;

    protected $quoteItemRepository;

    /**
     * @param SessionManager $sessionManager
     * @param HotaiAuthService $hotaiAuthService
     * @param UrlInterface $urlInterface
     * @param ActionFlag $actionFlag
     * @param ManagerInterface $messageManager
     * @param Session $checkoutSession
     * @param Data $splitCartHelper
     * @param \Branch8\Customer\Helper\Data $b8CustomerHelper
     * @param HotaiShippingHelper $hotaiShippingHelper
     * @param MarketplaceHelper $marketPlaceDataHelper
     * @param QuoteRepository $quoteRepository
     * @param QuoteItemRepository $quoteItemRepository
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\Session $customerSession
     * @param HelperData $subAccountHelper
     * @param \Branch8\Checkout\Helper\Data $b8CheckoutHelper
     */
    public function __construct(
        SessionManager $sessionManager,
        HotaiAuthService $hotaiAuthService,
        UrlInterface $urlInterface,
        ActionFlag $actionFlag,
        ManagerInterface $messageManager,
        Session $checkoutSession,
        Data  $splitCartHelper,
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        HotaiShippingHelper $hotaiShippingHelper,
        \Webkul\Marketplace\Helper\Data $marketPlaceDataHelper,
        QuoteRepository $quoteRepository,
        QuoteItemRepository $quoteItemRepository,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        HelperData $subAccountHelper,
        \Branch8\Checkout\Helper\Data $b8CheckoutHelper
    ) {

        $this->hotaiAuthService = $hotaiAuthService;
        $this->sessionManager   = $sessionManager;
        $this->_urlInterface = $urlInterface;
        $this->actionFlag = $actionFlag;
        $this->_messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->splitCartHelper = $splitCartHelper;
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->hotaiShippingHelper = $hotaiShippingHelper;
        $this->marketPlaceDataHelper = $marketPlaceDataHelper;
        $this->registry = $registry;
        $this->customerSession = $customerSession;
        $this->subAccountHelper = $subAccountHelper;
        $this->b8CheckoutHelper = $b8CheckoutHelper;
        $this->quoteRepository = $quoteRepository;
        $this->quoteItemRepository = $quoteItemRepository;
    }

    /**
     * Refresh Hotai Token
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {

        try {
//            $this->sessionManager->setCustomRefererUrl('checkout');
            $hotaiToken = isset($this->sessionManager->getData()['hotai_token']) ??'';

            if (!$hotaiToken && false) {
                $loginUrl = $this->hotaiAuthService->getLoginUrl();
                $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                $controller = $observer->getControllerAction();
                $response = $controller->getResponse();

                $this->sessionManager->setCustomerRefererUrl($this->_urlInterface->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]));
                return $response->setRedirect($loginUrl);
            }

            /**
             * Validate OOS product in cart
             * Validate disabled product
             * Validate number of item
             */
            $quote = $this->checkoutSession->getQuote();
            $quoteItems = $quote->getItemsCollection();
            $checkedItems = [];
            foreach($quoteItems as $_quoteItem){
                if(!$_quoteItem->getAvailableToCheckout()){
                    continue;
                }
                $productItem = $_quoteItem->getProduct();
                $this->productCartItem[$_quoteItem->getId()] = $productItem;

                if ($productItem->getData('individual_product')) {
                    $checkedItems[] = $_quoteItem->getId();
                }
            }

            if(count($checkedItems)){
                foreach($quoteItems as &$qItem){
                    if(!in_array($qItem->getId(), $checkedItems)){
                        $qItem->setAvailableToCheckout(0);
                    }else{
                        $qItem->setAvailableToCheckout(1);
                    }
                }
                $this->quoteRepository->save($quote);
            }
            $errorProductName = [];
            $items = [];

            // Add function to reload cart for product variation
            if($this->registry->registry('reload_cart')){
                //$message = __('There are some products that are not available to order. Please uncheck them or remove from shopping cart. Please see : %1', implode(', ', $errorProductName ));
                return $this->redirectToCart($observer, '');
            }
            $isAllVirtual = true;
            // $quoteItems = $quote->getItemsCollection();
            foreach ($quoteItems as $_item) {
                if(!$_item->getAvailableToCheckout()){
                    continue;
                }
                $productItem = $this->productCartItem[$_item->getId()];
                if($_item->getHasError()){
                    $errorProductName[] = $productItem->getName();
                }

                //onnly get parent item
                if (!$_item->isDeleted() && !$_item->getParentItemId() && !$_item->getParentItem()) {
                    $_item->setShippingMethodFromProduct($productItem->getShippingMethod());
                    $items[$_item->getId()] = $_item;
                    if($_item->getProductType() != \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL){
                        $isAllVirtual = false;
                    }
                }
            }
            if(count($errorProductName) > 0){
                $message = __('There are some products that are not available to order. Please uncheck them or remove from shopping cart. Please see : %1', implode(', ', $errorProductName ));
                return $this->redirectToCart($observer, $message);
            }
            //Validate no Item available to checkout
            if(count($items) == 0){
                $message = __('You have not selected any products yet.');
                return $this->redirectToCart($observer, $message);
            }

            /**
             * Validate the shipping method of product in sub-order does not match seller shipping method setting
             */
            $allItems = $items;
            $itemsFollowSellers = [];
            foreach($items as $_item){
                $type = $this->splitCartHelper->getSplitType($allItems, $_item);
                $proId = $_item->getProduct()->getId();
                $sellerId = (int) $this->marketPlaceDataHelper->getSellerIdByProductId($proId);
                $itemsFollowSellers[$sellerId][$type][] = $_item;
            }
            $methodMapping = $this->hotaiShippingHelper->mappingMethod();
            $productShippingMethodData = [];
            $countVirtual = 0;
            $countTotal = 0;
            foreach($itemsFollowSellers as $sellerId => $itemsFollowSeller){
                $seller = $this->splitCartHelper->getSellerBySellerId($sellerId);
                $sellerShippingMethod = explode(',', (string)$seller['shipping_methods']);

                foreach($itemsFollowSeller as $subCarts){
                    foreach($subCarts as $subCartItem){
                        if(!$subCartItem->getAvailableToCheckout()){
                            continue;
                        }
                        if(isset($this->productCartItem[$subCartItem->getId()])){
                            $productCartItem = $this->productCartItem[$subCartItem->getId()];
                        }else{
                            $productCartItem = $subCartItem->getProduct();
                        }
                        $countTotal ++;
                        if(
                            ($subCartItem->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL) ||
                            $subCartItem->getProductType() == Type::TYPE_DOWNLOADABLE ||
                            (
                                $productCartItem->getTypeId() == \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD &&
                                $productCartItem->getGiftcardType() == \Magento\GiftCard\Model\Giftcard::TYPE_VIRTUAL
                            )

                        ){
                            $countVirtual ++;
                            continue;
                        }
                        $productShippingMethods = explode(',', (string)$subCartItem->getShippingMethodFromProduct());
                        $productMappingMethod = [];
                        foreach ($productShippingMethods as $method) {
                            if(isset($methodMapping[$method])){
                                $productMappingMethod[] = $methodMapping[$method];
                            }
                        }
                        if(empty($productShippingMethodData)){
                            $productShippingMethodData = $productMappingMethod;
                        }
                        $productShippingMethodData = array_intersect($productMappingMethod, $productShippingMethodData);
                        if(empty($productShippingMethodData)){
                            $message = __('Products in the shopping cart need to have the same shipping method.');
                            foreach($quoteItems as $_itemToUncheck){
                                $_itemToUncheck->setAvailableToCheckout(0);
                            }
                            $this->quoteRepository->save($quote);
                            return $this->redirectToCart($observer, $message);
                        }

                        if(empty(array_intersect($productMappingMethod, $sellerShippingMethod))){
                            $message = __(\Branch8\SellerContactInformation\Helper\SellerShipping::CART_VALIDATE_SELLER_PRODUCT_SHIPPING_METHOD_ERROR);
                            return $this->redirectToCart($observer, $message);
                        }
                    }
                }
            }
            /**
             * Validate intesection shipping method
             * if not intersect method and there is a physical product in cart
             * Redirect to shopping cart page
             * Note: move code to if(empty($productShippingMethodData))
             */
            // if(!count($productShippingMethodData) && ($countTotal != $countVirtual)){
            //     $message = __('Products in the shopping cart need to have the same shipping method.');
            //     foreach($quoteItems as $_itemToUncheck){
            //         $_itemToUncheck->setAvailableToCheckout(0);
            //     }
            //     $this->quoteRepository->save($quote);
            //     return $this->redirectToCart($observer, $message);
            // }

            /**
             * If all items are virtual product + Customer has no address
             */
            $isNoAddress = $this->b8CheckoutHelper->isCustomerNoAddress();
            if(!$isAllVirtual || !$isNoAddress){
                $isPlaceHolderAddress = $this->b8CheckoutHelper->isPlaceHodlerBilling($quote);
                if($isPlaceHolderAddress){
                    $this->b8CheckoutHelper->clearBillingAddress($quote);
                }
            }

        } catch (\Exception $e) {
            $message = __('Please Relogin / Login With Hotai Panel.');
            return $this->redirectToCart($observer, $message);
        }
    }

    /**
     * redirectToCart
     *
     * @param  \Magento\Framework\Event\Observer $observer
     * @param  string $message
     */
    protected function redirectToCart($observer, $message){
        if(!empty($message)){
            $this->_messageManager->addErrorMessage($message);
        }
        $customRedirectionUrl = $this->_urlInterface->getUrl('checkout/cart');
        if($this->b8CustomerHelper->isSeller() || $this->b8CustomerHelper->isWaitForSeller() || $this->subAccountHelper->isSubAccount()){
            $customRedirectionUrl = $this->_urlInterface->getUrl('/');
        }
        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
        $controller = $observer->getControllerAction();
        $response = $controller->getResponse();
        return $response->setRedirect($customRedirectionUrl);
    }
}
