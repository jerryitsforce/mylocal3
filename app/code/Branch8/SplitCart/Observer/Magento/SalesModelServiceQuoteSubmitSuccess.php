<?php

namespace Branch8\SplitCart\Observer\Magento;

use Branch8\MarketPlaceParentOrderAllowProductOOSInCart\Model\Config;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;
use Tigren\SplitCart\Helper\Data;
use Tigren\SplitCart\Logger\Logger;
use Tigren\SplitCart\Model\Session as SplitCartSession;

class SalesModelServiceQuoteSubmitSuccess implements ObserverInterface{

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var GuestCartManagementInterface
     */
    private $guestCartManagement;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CartItemInterface[]
     */
    private $items = [];

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CartItemInterfaceFactory
     */
    private $cartItemFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var SplitCartSession
     */
    private $splitCartSession;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param CheckoutSession $checkoutSession
     * @param CartItemInterfaceFactory $cartItemFactory
     * @param CartManagementInterface $cartManagement
     * @param GuestCartManagementInterface $guestCartManagement
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param Registry $registry
     * @param CollectionFactory $productCollectionFactory
     * @param Logger $logger
     * @param Data $data
     * @param SplitCartSession $splitCartSession
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CartItemInterfaceFactory $cartItemFactory,
        CartManagementInterface $cartManagement,
        GuestCartManagementInterface $guestCartManagement,
        CartRepositoryInterface $cartRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        Registry $registry,
        CollectionFactory $productCollectionFactory,
        Logger $logger,
        Data $data,
        SplitCartSession $splitCartSession,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        SerializerInterface $serializer
    ) {
        $this->cartItemFactory = $cartItemFactory;
        $this->cartManagement = $cartManagement;
        $this->guestCartManagement = $guestCartManagement;
        $this->cartRepository = $cartRepository;
        $this->checkoutSession = $checkoutSession;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->registry = $registry;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
        $this->helper = $data;
        $this->splitCartSession = $splitCartSession;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->serializer = $serializer;
    }

    public function execute(
        Observer $observer
    ) {
        $enableSplitCart = $this->helper->isEnabledSplitCart();
        if ($enableSplitCart) {
            try {
                /** @var Quote $quote */
                $quote = $observer->getEvent()->getQuote();

                $customerId = $quote->getCustomerId();
                if ($customerId) {
                    //disable all quotes
                    $conn = $this->quoteCollectionFactory->create()->getConnection();
                    $sqlDeactiveOtherCustomerQuote = 'update quote set is_active = 0 where customer_id='.$customerId;
                    $conn->query($sqlDeactiveOtherCustomerQuote);
                }

                $remainingItems = [];
                foreach ($quote->getAllItems() as $item) {
                    if (!$item->getAvailableToCheckout()) {
                        $remainingItems[] = $item;
                    }
                }

                if ($remainingItems) {
                    // Save current quote
//                    $quote->setIsActive(false);
//                    $this->cartRepository->save($quote);

                    if ($customerId) {
                        //disable all quote
                        $cartId = $this->cartManagement->createEmptyCartForCustomer($customerId);
                        $cart = $this->cartRepository->get($cartId);
                    } else {
                        $quoteMaskedId = $this->guestCartManagement->createEmptyCart();

                        $quoteIdMask = $this->quoteIdMaskFactory->create();
                        $quoteIdMask->load($quoteMaskedId, 'masked_id');
                        $cartId = $quoteIdMask->getQuoteId();
                        $this->checkoutSession->setQuoteId($cartId);
                        $cart = $this->checkoutSession->getQuote();

                        $this->splitCartSession->setSplitQuoteId($cart->getId());
                    }
                    // Bypass cart and item error check for adding remaining cart items to cart
                    if($this->registry->registry('split_cart_adding_remaining_items_to_cart') !== null) {
                        $this->registry->unregister('split_cart_adding_remaining_items_to_cart');
                    }
                    $this->registry->register('split_cart_adding_remaining_items_to_cart', true);

                    /*enable allow add OOS product*/
                    $this->registry->register(Config::ALLOW_REORDER_OOS_PRODUCT_KEY, true);
                    
                    $this->addItemsToCart($cart, $remainingItems);
                    
                    $this->cartRepository->save($cart);

                    if ($cart instanceof DataObject) {
                        $cart->setData('totals_collected_flag', false);
                    }
                }
            } catch (\Exception $e) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_SplitCart', 'debuglog')){
                    $this->logger->debug(__($e->getMessage()));
                }
            }
        }
    }

    /**
     * Add collections of order items to cart.
     *
     * @param Quote $cart
     * @param ItemCollection $orderItems
     * @return void
     * @throws LocalizedException
     */
    private function addItemsToCart(Quote $cart, $orderItems): void
    {
        $orderItemProductIds = [];
        $orderItemsByProductId = $orderItems;

        foreach ($orderItems as $item) {
            if (!$item->getAvailableToCheckout()) {
                if ($item->getParentItem() === null) {
                    $orderItemProductIds[] = $item->getProductId();
                    $orderItemsByProductId[$item->getProductId()][$item->getId()] = $item;
                }
            }
        }

        $products = $this->getOrderProducts($orderItemProductIds);

        // compare founded products and throw an error if some product not exists
        $productsNotFound = array_diff($orderItemProductIds, array_keys($products));
        if (!empty($productsNotFound)) {
            foreach ($productsNotFound as $productId) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_SplitCart', 'debuglog')){
                    $this->logger->debug(__('Could not find a product with ID "%1"', $productId));
                }
            }
        }

        foreach ($orderItems as $item) {
            if (!isset($products[$item->getProductId()])) {
                continue;
            }
            $product = $products[$item->getProductId()];
            if (!$product) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_SplitCart', 'debuglog')){
                    $this->logger->debug(__('Could not find a product with ID "%1"', $productId));
                }
            }
            $this->addItemToCart($item, $cart, clone $product);
        }
    }

    /**
     * Get order products by store id and order item product ids.
     *
     * @param int[] $orderItemProductIds
     * @return array
     * @throws LocalizedException
     */
    private function getOrderProducts(array $orderItemProductIds): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addIdFilter($orderItemProductIds)
            ->addStoreFilter()
            ->addAttributeToSelect('*')
            ->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner')
            ->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner')
            ->addOptionsToResult();

        return $collection->getItems();
    }

    /**
     * Adds order item product to cart.
     *
     * @param CartItemInterface $orderItem
     * @param Quote $cart
     * @param $product
     * @return void
     */
    private function addItemToCart($orderItem, Quote $cart, $product)
    {
        $info = $orderItem->getOptionByCode('info_buyRequest')->getValue();
        $info = is_string($info) ? $this->serializer->unserialize($info) : $info;
        $options = $orderItem->getOptions();
        if (!empty($options) && is_array($info) && isset($info['options'])) {
            foreach ($options as $option) {
                if (array_key_exists($option['option_id'], $info['options'])) {
                    try {
                        $value = $this->serializer->unserialize($option['option_value']);
                        $info['options'][$option['option_id']] = $value;
                    } catch (\InvalidArgumentException $exception) {
                        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_SplitCart', 'exceptionlog')){
                            $this->logger->error($exception);
                        }
                    }
                }
            }
        }

        $infoBuyRequest = new DataObject($info);
        $infoBuyRequest->setQty($orderItem->getQty());

        try {
            $cart->addProduct($product, $infoBuyRequest);
        } catch (\Magento\Framework\Exception\LocalizedException|\Throwable $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_SplitCart', 'exceptionlog')){
                $this->logger->critical($e);
            }
        }
    }
}
