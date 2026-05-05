<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Model;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Cart\CustomerCartResolver;
use Magento\Quote\Model\GuestCart\GuestCartResolver;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Helper\Reorder as ReorderHelper;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Reorder\OrderInfoBuyRequestGetter;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as ItemCollection;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Reorder\Data;

class Reorder implements ReorderInterface
{
    /**#@+
     * Error message codes
     */
    private const ERROR_PRODUCT_NOT_FOUND = 'PRODUCT_NOT_FOUND';
    private const ERROR_INSUFFICIENT_STOCK = 'INSUFFICIENT_STOCK';
    private const ERROR_NOT_SALABLE = 'NOT_SALABLE';
    private const ERROR_REORDER_NOT_AVAILABLE = 'REORDER_NOT_AVAILABLE';
    private const ERROR_UNDEFINED = 'UNDEFINED';
    /**#@-*/

    /**
     * List of error messages and codes.
     */
    private const MESSAGE_CODES = [
        'The selected quantity exceeds the purchasable limit; unable to add to cart for now.' => self::ERROR_NOT_SALABLE,
        'Product that you are trying to add is not available' => self::ERROR_NOT_SALABLE,
        'This product is out of stock' => self::ERROR_NOT_SALABLE,
        'There are no source items' => self::ERROR_NOT_SALABLE,
        'The fewest you may purchase is' => self::ERROR_INSUFFICIENT_STOCK,
        'The most you may purchase is' => self::ERROR_INSUFFICIENT_STOCK,
        'The requested qty is not available' => self::ERROR_INSUFFICIENT_STOCK,
    ];

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var ReorderHelper
     */
    private $reorderHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var Data\Error[]
     */
    private $errors = [];

    /**
     * @var CustomerCartResolver
     */
    private $customerCartProvider;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var GuestCartResolver
     */
    private $guestCartResolver;

    /**
     * @var OrderInfoBuyRequestGetter
     */
    private $orderInfoBuyRequestGetter;
    private StoreManager $storeManager;

    private $registry;
    private Config $config;

    /**
     * @param OrderFactory $orderFactory
     * @param CustomerCartResolver $customerCartProvider
     * @param GuestCartResolver $guestCartResolver
     * @param CartRepositoryInterface $cartRepository
     * @param ReorderHelper $reorderHelper
     * @param LoggerInterface $logger
     * @param ProductCollectionFactory $productCollectionFactory
     * @param OrderInfoBuyRequestGetter $orderInfoBuyRequestGetter
     * @param StoreManager $storeManager
     * @param Config $config
     * @param Registry $registry
     */
    public function __construct(
        OrderFactory              $orderFactory,
        CustomerCartResolver      $customerCartProvider,
        GuestCartResolver         $guestCartResolver,
        CartRepositoryInterface   $cartRepository,
        ReorderHelper             $reorderHelper,
        LoggerInterface           $logger,
        ProductCollectionFactory  $productCollectionFactory,
        OrderInfoBuyRequestGetter $orderInfoBuyRequestGetter,
        StoreManager              $storeManager,
        Config                    $config,
        Registry                  $registry
    )
    {
        $this->registry = $registry;
        $this->orderFactory = $orderFactory;
        $this->cartRepository = $cartRepository;
        $this->reorderHelper = $reorderHelper;
        $this->logger = $logger;
        $this->customerCartProvider = $customerCartProvider;
        $this->guestCartResolver = $guestCartResolver;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->orderInfoBuyRequestGetter = $orderInfoBuyRequestGetter;
        $this->storeManager = $storeManager;
        $this->registry;
        $this->config = $config;
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return Data\ReorderOutput
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(ParentOrderInterface $parentOrder)
    {
        $subOrders = $parentOrder->getSubOrders();
        if (empty($subOrders)) {
            throw new InputException(
                __('Can not re-order for this order')
            );
        }
        $storeId = $this->storeManager->getStore()->getId();
        $customerId = (int)$parentOrder->getDetail()->getCustomerId();
        $this->errors = [];
        $cart = $customerId === 0
            ? $this->guestCartResolver->resolve()
            : $this->customerCartProvider->resolve($customerId);
        if (!$this->reorderHelper->isAllowed($parentOrder->getStore())) {
            $this->addError((string)__('Reorders are not allowed.'), self::ERROR_REORDER_NOT_AVAILABLE);
            return $this->prepareOutput($cart);
        }
        $this->addItemsToCart($cart, $parentOrder->getAllItems(), (int)$storeId);
        try {
            $this->cartRepository->save($cart);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // handle exception from \Magento\Quote\Model\QuoteRepository\SaveHandler::save
            $this->addError($e->getMessage());
        }
        $savedCart = $this->cartRepository->get($cart->getId());
        return $this->prepareOutput($savedCart);

    }

    /**
     * @param Quote $cart
     * @param array $orderItems
     * @param int $storeId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addItemsToCart(Quote $cart, array $orderItems, int $storeId): void
    {
        $allowReorderOOSproduct = $this->allowReorderOosProduct();
        if ($allowReorderOOSproduct) {
            /*
                     enable flag to cart will not remove item from cart if any error
                     @see Branch8/MarketPlaceParentOrderFrontendUi/Plugin/Magento/Quote/Model/QuotePlugin
            */
            $this->registry->register(Config::ALLOW_REORDER_OOS_PRODUCT_KEY, true);
        }
        $orderItemProductIds = [];
        /** @var \Magento\Sales\Model\Order\Item[] $orderItemsByProductId */
        $orderItemsByProductId = [];
        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($orderItems as $item) {
            if ($item->getParentItem() === null) {
                $orderItemProductIds[] = $item->getProductId();
                $orderItemsByProductId[$item->getProductId()][$item->getId()] = $item;
            }
        }
        $products = $this->getOrderProducts($storeId, $orderItemProductIds);
        // compare founded products and throw an error if some product not exists
        $productsNotFound = array_diff($orderItemProductIds, array_keys($products));
        if (!empty($productsNotFound)) {
            foreach ($productsNotFound as $productId) {
                /** @var \Magento\Sales\Model\Order\Item $orderItemProductNotFound */
                $this->addError(
                    (string)__('Could not find a product with ID "%1"', $productId),
                    self::ERROR_PRODUCT_NOT_FOUND
                );
            }
        }
        foreach ($orderItemsByProductId as $productId => $orderItems) {
            if (!isset($products[$productId])) {
                continue;
            }
            $product = $products[$productId];
            foreach ($orderItems as $orderItem) {
                $this->addItemToCart($orderItem, $cart, clone $product);
            }
        }
        try {
            // unset registry key to prevent issue
            if ($this->registry->registry(Config::ALLOW_REORDER_OOS_PRODUCT_KEY)) {
                $this->registry->unregister(Config::ALLOW_REORDER_OOS_PRODUCT_KEY);
            }
        } catch (\Exception $exception) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceParentOrderFrontendUi', 'exceptionlog')){
                $this->logger->critical($exception);
            }
        }
    }

    /**
     * @param int $storeId
     * @param array $orderItemProductIds
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getOrderProducts(int $storeId, array $orderItemProductIds): array
    {
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        /**
         * If allow product OOS can be reordered, so we will by pass let collection can filter all products
         */
        if ($this->allowReorderOosProduct()) {
            $collection->setFlag('has_stock_status_filter', true);
        }
        $collection->setStore($storeId)
            ->addIdFilter($orderItemProductIds)
            ->addStoreFilter()
            ->addAttributeToSelect('*')
            ->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner')
            ->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner')
            ->addOptionsToResult();

        return $collection->getItems();
    }

    /**
     * @return bool
     */
    private function allowReorderOosProduct()
    {
        return $this->config->allowReorderOOSProduct();
    }

    /**
     * Adds order item product to cart.
     *
     * @param OrderItemInterface $orderItem
     * @param Quote $cart
     * @param ProductInterface $product
     * @return void
     */
    private function addItemToCart(OrderItemInterface $orderItem, Quote $cart, ProductInterface $product): void
    {
        $infoBuyRequest = $this->orderInfoBuyRequestGetter->getInfoBuyRequest($orderItem);
        $allowReorderOOSproduct = $this->allowReorderOosProduct();
        $addProductResult = null;
        try {
            $addProductResult = $cart->addProduct($product, $infoBuyRequest);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->addError($this->getCartItemErrorMessage($orderItem, $product, $e->getMessage()));
        } catch (\Throwable $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceParentOrderFrontendUi', 'exceptionlog')){
                $this->logger->critical($e);
            }
            $this->addError($this->getCartItemErrorMessage($orderItem, $product), self::ERROR_UNDEFINED);
        }

        // error happens in case the result is string
        if (is_string($addProductResult)) {
            $errors = array_unique(explode("\n", $addProductResult));
            foreach ($errors as $error) {
                $this->addError($this->getCartItemErrorMessage($orderItem, $product, $error));
            }
        }
    }

    /**
     * Add order line item error
     *
     * @param string $message
     * @param string|null $code
     * @return void
     */
    private function addError(string $message, string $code = null): void
    {
        $this->errors[] = new Data\Error(
            $message,
            $code ?? $this->getErrorCode($message)
        );
    }

    /**
     * Get message error code. Ad-hoc solution based on message parsing.
     *
     * @param string $message
     * @return string
     */
    private function getErrorCode(string $message): string
    {
        $code = self::ERROR_UNDEFINED;

        $matchedCodes = array_filter(
            self::MESSAGE_CODES,
            function ($key) use ($message) {
                return false !== strpos($message, $key);
            },
            ARRAY_FILTER_USE_KEY
        );

        if (!empty($matchedCodes)) {
            $code = current($matchedCodes);
        }

        return $code;
    }

    /**
     * Prepare output
     *
     * @param CartInterface $cart
     * @return Data\ReorderOutput
     */
    private function prepareOutput(CartInterface $cart): Data\ReorderOutput
    {
        $output = new Data\ReorderOutput($cart, $this->errors);
        $this->errors = [];
        // we already show user errors, do not expose it to cart level
        $cart->setHasError(false);
        return $output;
    }

    /**
     * Get error message for a cart item
     *
     * @param Item $item
     * @param Product $product
     * @param string|null $message
     * @return string
     */
    private function getCartItemErrorMessage(Item $item, Product $product, string $message = null): string
    {
        // try to get sku from line-item first.
        // for complex product type: if custom option is not available it can cause error
        $sku = $item->getSku() ?? $product->getData('sku');
        $name = $product->getName();
        return (string)($message
            ? __('Error when process adding "%1" product to cart : %2', $name, $message)
            : __('Could not add the product with SKU "%1" to the shopping cart', $sku));
    }
}
