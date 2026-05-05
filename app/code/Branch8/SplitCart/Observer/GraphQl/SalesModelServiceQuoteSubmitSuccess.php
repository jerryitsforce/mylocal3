<?php

namespace Branch8\SplitCart\Observer\GraphQl;

use Branch8\MarketPlaceParentOrderAllowProductOOSInCart\Model\Config;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;
use Tigren\SplitCart\Logger\Logger;

class SalesModelServiceQuoteSubmitSuccess implements ObserverInterface{


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

    public function __construct(
        CartItemInterfaceFactory $cartItemFactory,
        CartManagementInterface $cartManagement,
        GuestCartManagementInterface $guestCartManagement,
        CartRepositoryInterface $cartRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        Registry $registry,
        CollectionFactory $productCollectionFactory,
        Logger $logger
    ) {
        $this->cartItemFactory = $cartItemFactory;
        $this->cartManagement = $cartManagement;
        $this->guestCartManagement = $guestCartManagement;
        $this->cartRepository = $cartRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->registry = $registry;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function execute(
        Observer $observer
    ) {
        try {
            /** @var Quote $quote */
            $quote = $observer->getEvent()->getQuote();

            $remainingItems = [];

            foreach ($quote->getAllItems() as $item) {
                if (!$item->getAvailableToCheckout()) {
                    $remainingItems[] = $item;
                }
            }

            // Save current quote
            $quote->setIsActive(false);
            $this->cartRepository->save($quote);

            $customerId = $quote->getCustomerId();

            if ($customerId) {
                $cartId = $this->cartManagement->createEmptyCartForCustomer($customerId);
            } else {
                $quoteMaskedId = $this->guestCartManagement->createEmptyCart();

                $this->registry->register('new_cart_mask_id', $quoteMaskedId);

                $quoteIdMask = $this->quoteIdMaskFactory->create();
                $quoteIdMask->load($quoteMaskedId, 'masked_id');
                $cartId = $quoteIdMask->getQuoteId();
            }

            $cart = $this->cartRepository->get($cartId);

            // Bypass cart and item error check for adding remaining cart items to cart
            $this->registry->register('split_cart_adding_remaining_items_to_cart', true);
            $this->addItemsToCart($remainingItems);
            /*enable allow add OOS product*/
            $this->registry->register(Config::ALLOW_REORDER_OOS_PRODUCT_KEY, true);
            $cart->setItems($this->items);
            $this->cartRepository->save($cart);

            if ($cart instanceof DataObject) {
                $cart->setData('totals_collected_flag', false);
            }
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_SplitCart', 'debuglog')){
                $this->logger->debug(__($e->getMessage()));
            }
        }
    }

    /**
     * Add collections of order items to cart.
     *
     * @param ItemCollection $orderItems
     * @return void
     * @throws LocalizedException
     */
    private function addItemsToCart($orderItems): void
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
            $this->addItemToCart($item, $product);
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
     * @param $product
     * @return \Tigren\SplitCart\Observer\GraphQl\SalesModelServiceQuoteSubmitSuccess
     */
    private function addItemToCart($orderItem, $product)
    {
        /** @var CartItemInterface $cartItem */
        $cartItem = $this->cartItemFactory->create();
        $cartItem->setSku($product->getSku());
        $cartItem->setName($orderItem->getName());
        /**
         * Set Product object to quote item, beccause the qty validation is require this object on validate cart qty
         */
        $cartItem->setProduct($product);
        $cartItem->setQty($orderItem->getQty());
        $cartItem->setPrice($orderItem->getPrice());
        $cartItem->setProductType($orderItem->getProductType());

        if ($orderItem->getProductOption()) {
            $cartItem->setProductOption($orderItem->getProductOption());
        }

        $this->items[] = $cartItem;
        return $this;
    }
}