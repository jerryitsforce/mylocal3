<?php

namespace Branch8\Checkout\Observer\Controller;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Magento\Framework\App\ActionFlag;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepository;
use Magento\Framework\UrlInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Model\Ticket\Reason as TicketReason;
use Branch8\WebkulMpsplitorder\Helper\CacheLock as SplitOrderCacheLock;

class PredispatchCheckoutCart implements \Magento\Framework\Event\ObserverInterface{

    const FULL_CONTROLLER_ACTION = 'full_controller_action';
    const LOG_FOLDER_NAME = 'Checkout/Observer/PredispatchCheckoutCart';
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $session;
    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;
    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;
    /**
     * @var ActionFlag
     */
    protected $actionFlag;
    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var CustomerCart
     */
    protected $cart;

    /**
     * @var VirtualProductHelper
     */
    protected $virtualProductHelper;

    /**
     * @var HotaiCoreCommonHelper
     */
    protected $hotaiCoreCommonHelper;

    /**
     * @var SplitOrderCacheLock
     */
    protected $splitOrderCacheLock;

    protected $_cookieManager;

    protected $quoteAddress;

    protected $_conn;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param QuoteRepository $quoteRepository
     * @param UrlInterface $url
     * @param CustomerCart $cart
     * @param ActionFlag $actionFlag
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        QuoteRepository $quoteRepository,
        \Magento\Framework\UrlInterface $url,
        CustomerCart $cart,
        ActionFlag $actionFlag,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Quote\Model\Quote\Address $quoteAddress,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        VirtualProductHelper $virtualProductHelper,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        SplitOrderCacheLock $splitOrderCacheLock
    ) {
        $this->session = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->url = $url;
        $this->cart = $cart;
        $this->actionFlag = $actionFlag;
        $this->redirect = $redirect;
        $this->_cookieManager = $cookieManager;
        $this->quoteAddress = $quoteAddress;
        $this->_conn = $resourceConnection->getConnection();
        $this->virtualProductHelper = $virtualProductHelper;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->splitOrderCacheLock = $splitOrderCacheLock;
    }

    public function execute($observer){
        $quoteId = null;
        if($this->session->getQuoteId()){
            $quoteId = $this->session->getQuoteId();
        }
        $controller = $observer->getControllerAction();
        if($quoteId) {
            $isUpdate = false;

            $quote = $this->session->getQuote();
            $allItems = $quote->getAllVisibleItems();
            foreach ($allItems as $item) {
                $product = $item->getProduct();
                if ($product->getData('individual_product')) {
                    $itemId = $item->getItemId();
                    $this->cart->removeItem($itemId);//->save();
                    $isUpdate = true;
                }
            }

            if ($quote->getPointUsedTotal() !== null) {
                $quote->setPointUsedTotal(null);
                $quote->setPointDiscountTotal(null);
                $isUpdate = true;
            }

            /**
             * Reset coupon if visit cart page,
             * Reload cart page: keep coupon
             */
            $cookieControllerAction = $this->_cookieManager->getCookie(self::FULL_CONTROLLER_ACTION);
            $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
            //reset shipping method
            if ($shippingMethod) {
                $shippingAddress = $quote->getShippingAddress();
                $shippingAddressId = $shippingAddress->getId();
                /**
                 * Delete shipping rate relate to address id
                 */
                /**
                 * Deadlock issue HTGO2-2244
                 */
                //$this->_conn->delete('quote_shipping_rate', 'address_id='.$shippingAddressId);

                if($cookieControllerAction != $controller->getRequest()->getFullActionName()){
                    $quote->setCouponCode('');
                }

                $quote->setTotalsCollectedFlag(false);
                $shippingAddress->unsetData('cached_items_all');
                $shippingAddress->unsetData('cached_items_nominal');
                $shippingAddress->unsetData('cached_items_nonnominal');
                $shippingAddress->setShippingMethod(NULL);
                $shippingAddress->setShippingDescription('Reset Shipping on cart page');
                $shippingAddress->setShippingAmount(0);
                $shippingAddress->setBaseShippingAmount(0);
                $quote->setShippingAddress($shippingAddress);
                $quote->collectTotals();
                $isUpdate = true;
            } elseif ($cookieControllerAction != $controller->getRequest()->getFullActionName()){
                /**
                 * Remove coupon
                 */
                $quote->setCouponCode('')->collectTotals();
                $isUpdate = true;
            }
            //uncheck error item
            $quoteItems = $quote->getItemsCollection();
            foreach($quoteItems as &$_item){
                if($_item->getHasError()) {
                    $isUpdate = true;
                    $_item->setAvailableToCheckout(0);
                    $itemChildren = $_item->getChildren();
                    if ($itemChildren) {
                        foreach ($itemChildren as &$itemChild) {
                            $itemChild->setAvailableToCheckout(0);
                        }
                    }
                }

                // uncheck ticket without enough stock
                try {
                    if ($_item->getAvailableToCheckout() == 1 && $this->virtualProductHelper->isBatchImportTicketProduct($_item->getProductId())) {
                        $checkResultArray = $this->virtualProductHelper->checkIfQuantityEnoughByCustomOptionAndRequestQuantity(
                            $_item->getProductId(),
                            $this->virtualProductHelper->getBatchSettingCustomOptionValueForQuoteItemFlow($_item)->getTitle(),
                            $_item->getQty()
                        );

                        $isBatchQtyEnough = $checkResultArray['result'] ?? false;
                        $reason = $checkResultArray['reason'] ?? null;

                        // log for check
                        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Checkout', 'predispatch_checkout_cart')){
                            $this->hotaiCoreCommonHelper->writeLog(json_encode([
                                'title' => 'Check if ticket without enough stock.',
                                'item_id' => $_item->getId(),
                                'product_id' => $_item->getProductId(),
                                'batch_code' => $this->virtualProductHelper->getBatchSettingCustomOptionValueForQuoteItemFlow($_item)->getTitle(),
                                'request_quantity' => $_item->getQty(),
                                'check_result' => json_encode($checkResultArray ?? []),
                            ]), self::LOG_FOLDER_NAME);
                        }

                        if (!$isBatchQtyEnough) {
                            $_item->setAvailableToCheckout(0);
                            $isUpdate = true;

                            // Set to 0 not working, comment for now.
                            // if ($reason == TicketReason::REASON_FOR_OOS_CHECK_OUT_OF_SALE_TIME_WINDOW) {
                            //     $_item->setQty(0);
                            // }
                        }
                    }
                } catch (\Throwable $th) {
                    if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Checkout', 'predispatch_checkout_cart')){
                        $this->hotaiCoreCommonHelper->writeLog(json_encode([
                            'title' => 'Uncheck ticket without enough stock failed.',
                            'item_id' => $_item->getId(),
                            'product_id' => $_item->getProductId(),
                            'exception_message' => $th->getMessage()
                        ]), self::LOG_FOLDER_NAME);
                    }
                }

                $masterQuoteId = $quote->getId();
                $isSplitOrderProcedureLock = $this->splitOrderCacheLock->checkIsSplitOrderProcedureLockNow($masterQuoteId);
                $isQuoteItemUsedPoint = $_item->getData('row_total_point_used') || $_item->getData('row_total_point_discount');
                if (!$isSplitOrderProcedureLock && $isQuoteItemUsedPoint) {
                    $_item->setData('row_total_point_used', null);
                    $_item->setData('row_total_point_discount', null);
                    $isUpdate = true;
                }
            }

            if($isUpdate) {
                $quote->setTotalsCollectedFlag(false)->collectTotals();
                $this->quoteRepository->save($quote);
                $quoteId = $quote->getId();
                $this->session
                    //->clearQuote()
                    ->setQuoteId($quoteId);
            }
        }

    }

}
