<?php
namespace Branch8\WishlistStockAlert\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Branch8\WishlistStockAlert\Helper\StockChecker;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Branch8\WishlistStockAlert\Helper\Logger as CustomLogger;

class CheckStockAfterOrderObserver implements ObserverInterface
{
    protected $stockChecker;
    protected $productRepository;
    protected CustomLogger $logger;

    /**
     * @param StockChecker $stockChecker
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        StockChecker $stockChecker,
        ProductRepositoryInterface $productRepository,
        CustomLogger $logger
    ) {
        $this->stockChecker = $stockChecker;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            foreach ($order->getAllItems() as $item) {
                $productId = $item->getProductId();
                // Get SKU for MSI check
                try {
                    $product = $this->productRepository->getById($productId);
                    $sku = $product->getSku();
                    $this->stockChecker->checkAndQueueAlerts($productId, $sku);
                } catch (\Exception $e) {
                    $this->logger->error('Could not check stock for product: ' . $productId);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Check Stock After Order Error: ' . $e->getMessage());
        }

        return $this;
    }
}
