<?php

namespace Branch8\WishlistStockAlert\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Branch8\WishlistStockAlert\Model\ResourceModel\StockAlert\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Store\Model\StoreManagerInterface;
use Branch8\WishlistStockAlert\Helper\Logger as CustomLogger;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

class StockChecker extends AbstractHelper
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CustomLogger
     */
    protected CustomLogger $logger;

    // MSI Services
    protected $getProductSalableQty;
    /**
     * @var StockResolverInterface
     */
    protected $stockResolver;
    /**
     * @var IsProductSalableInterface
     */
    protected $isProductSalable;

    private ResourceConnection $resourceConnection;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param StockResolverInterface $stockResolver
     * @param IsProductSalableInterface $isProductSalable
     * @param ResourceConnection $resourceConnection
     * @param CustomLogger $logger
     */
    public function __construct(
        Context                       $context,
        CollectionFactory             $collectionFactory,
        StoreManagerInterface         $storeManager,
        GetProductSalableQtyInterface $getProductSalableQty,
        StockResolverInterface        $stockResolver,
        IsProductSalableInterface     $isProductSalable,
        ResourceConnection            $resourceConnection,
        CustomLogger               $logger
    )
    {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->stockResolver = $stockResolver;
        $this->isProductSalable = $isProductSalable;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param \Magento\Wishlist\Model\Item $item
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function checkStockWishListItem(\Magento\Wishlist\Model\Item $item)
    {
        $website = $this->storeManager->getWebsite();
        $infoBuyRequest = $item->getBuyRequest();
        if (!$infoBuyRequest) {
            return true;
        }
        $combo = $infoBuyRequest->getCombo() ?: 'NONE';
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
        $select = $this->resourceConnection->getConnection()->select()->from(
            ['stock' => 'branch8_options_stock_index'],
                ['is_salable' => 'is_salable']
        )->where('combo = ?', $combo)
            ->where('product_id = ?', $item->getProductId())
            ->where('stock_id = ?', $stock->getId());
        return (bool)$this->resourceConnection->getConnection()->fetchOne($select);
    }

    /**
     * @param $productId
     * @param $rowId
     * @param $combo
     * @return int
     */
    public function getSaleableQuantity($sku, $rowId, $combo = '')
    {
        if ($combo) {
            return $this->getVariantionStock($productId,$combo);
        } else {
            return $this->getSalableQuantity($sku);
        }
    }

    /**
     * @param $productId
     * @param $combo
     * @param $websiteCode
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getVariantionStock($productId, $combo, $websiteCode = null)
    {
        if (!$websiteCode) {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
        }
        $stock = $this->stockResolver->execute('website', $websiteCode);
        $stockId = $stock->getStockId();
        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from(['stock' => 'branch8_options_stock_index'], ['quantity'])
            ->where('product_id = ? ', $productId)
            ->where('combo = ?', $combo)
            ->where('stock_id = ?', $stockId);
        return (int)$this->resourceConnection->getConnection()->fetchOne($select);
    }
    /**
     * Check if product is in stock using MSI
     */
    public function isProductInStock($sku, $websiteCode = null)
    {
        try {
            if (!$sku) {
                return false;
            }
            if (!$websiteCode) {
                $websiteCode = $this->storeManager->getWebsite()->getCode();
            }
            // Get stock ID for website
            $stock = $this->stockResolver->execute('website', $websiteCode);
            $stockId = $stock->getStockId();
            // Check if product is salable
            $isSalable = $this->isProductSalable->execute($sku, $stockId);

            if ($isSalable) {
                // Get actual salable quantity
                $salableQty = $this->getProductSalableQty->execute($sku, $stockId);
                return $salableQty > 0;
            }

            return false;
        } catch (\Throwable $e) {
            $this->logger->error('MSI Stock Check Error: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * Get salable quantity for a product
     */
    public function getSalableQuantity($sku, $websiteCode = null)
    {
        try {
            if (!$sku) {
                return 0;
            }
            if (!$websiteCode) {
                $websiteCode = $this->storeManager->getWebsite()->getCode();
            }
            $stock = $this->stockResolver->execute('website', $websiteCode);
            $stockId = $stock->getStockId();
            return $this->getProductSalableQty->execute($sku, $stockId);
        } catch (\Throwable $e) {
            $this->logger->error('MSI Get Salable Qty Error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check and queue alerts for a product
     */
    public function checkAndQueueAlerts($productId, $sku = null)
    {
        // If SKU not provided, we need to get it from product
        if (!$sku) {
            // This would require ProductRepository - simplified for now
            $this->logger->warning('SKU not provided for product ' . $productId);
            return;
        }
        // Check if product is now in stock
        $isInStock = $this->isProductInStock($sku);
        if (!$isInStock) {
            return; // Not in stock, no alerts needed
        }
        $salableQty = $this->getSalableQuantity($sku);
        // Find all pending alerts for this product
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId)
            ->addFieldToFilter('was_out_of_stock_when_added', 1)
            ->addFieldToFilter('notification_sent', 0);
        $count = $collection->getSize();
        if ($count > 0) {
            $this->logger->info(sprintf(
                'Found %d pending alerts for product %d (SKU: %s, Salable Qty: %s)',
                $count,
                $productId,
                $sku,
                $salableQty
            ));
        }
        return $collection;
    }
}
