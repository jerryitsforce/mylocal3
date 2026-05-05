<?php

namespace Branch8\WishlistStockAlert\Observer;

use Branch8\WishlistStockAlert\Helper\StockChecker;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Branch8\WishlistStockAlert\Model\StockAlertFactory;
use Branch8\WishlistStockAlert\Api\StockAlertRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Branch8\WishlistStockAlert\Helper\Logger as CustomLogger;

class WishlistAddProductObserver implements ObserverInterface
{
    /**
     * @var StockAlertFactory
     */
    protected $stockAlertFactory;
    /**
     * @var StockAlertRepositoryInterface
     */
    protected $stockAlertRepository;
    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;
    protected CustomLogger $logger;

    private StockChecker $stockChecker;
    private ProductRepository $repository;

    /**
     * @param StockAlertFactory $stockAlertFactory
     * @param StockAlertRepositoryInterface $stockAlertRepository
     * @param StockChecker $stockChecker
     * @param ProductRepository $repository
     * @param CustomLogger $logger
     */
    public function __construct(
        StockAlertFactory             $stockAlertFactory,
        StockAlertRepositoryInterface $stockAlertRepository,
        StockChecker                  $stockChecker,
        ProductRepository             $repository,
        CustomLogger                  $logger
    )
    {
        $this->stockAlertFactory = $stockAlertFactory;
        $this->stockAlertRepository = $stockAlertRepository;
        $this->stockChecker = $stockChecker;
        $this->logger = $logger;
        $this->repository = $repository;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        try {
            $items = $observer->getEvent()->getItems();
            if (!$items || !is_array($items)) {
                $item = $observer->getEvent()->getItem();
                if ($item) {
                    $items = [$item];
                }
            }
            if (!$items) {
                return $this;
            }

            foreach ($items as $item) {
                $this->processWishlistItem($item);
            }
        } catch (\Exception $e) {
            $this->logger->error('Wishlist Stock Alert Add Error: ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * @param $item
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function processWishlistItem($item)
    {
        try {
            $productId = $item->getProductId();
            $wishlistItemId = $item->getId();
            $customerId = $item->getWishlistId() ? $item->getWishlist()->getCustomerId() : null;
            if (!$customerId || !$productId || !$wishlistItemId) {
                return;
            }
            $product = $this->repository->getById($productId);
            // Check if alert already exists for this wishlist item
            $existingAlert = $this->stockAlertRepository->getByWishlistItemId($wishlistItemId);
            if ($existingAlert->getId()) {
                return; // Already tracked
            }
            // Check stock status
            $isInStock = $this->stockChecker->checkStockWishListItem($item);
            $wasOutOfStock = ($isInStock) ? 0 : 1;
            // Create stock alert record
            $stockAlert = $this->stockAlertFactory->create();
            $stockAlert->setWishlistItemId($wishlistItemId);
            $stockAlert->setCustomerId($customerId);
            $stockAlert->setProductId($productId);
            $stockAlert->setProductName($product->getName());
            $infoBuyRequest = $item->getBuyRequest();
            $sableQuantity = $infoBuyRequest->getCombo() ? $this->stockChecker->getVariantionStock(
                $productId, $infoBuyRequest->getCombo()
            ) : $this->stockChecker->getSalableQuantity($product->getSku());
            $stockAlert->setCombo($infoBuyRequest->getCombo() ?: 'NONE');
            $stockAlert->setSaleableQtyWhenAdded($sableQuantity);
            $stockAlert->setWasOutOfStockWhenAdded($wasOutOfStock);
            $stockAlert->setNotificationSent(0);
            $this->stockAlertRepository->save($stockAlert);
            $this->logger->info(sprintf(
                'Stock Alert Created: Product %d, Customer %d, Was OOS: %s',
                $productId,
                $customerId,
                $wasOutOfStock ? 'Yes' : 'No'
            ));
        } catch (\Exception $e) {
            $this->logger->critical('Wishlist Stock Alert Add Error: ' . $e->getMessage());
        }
    }
}
