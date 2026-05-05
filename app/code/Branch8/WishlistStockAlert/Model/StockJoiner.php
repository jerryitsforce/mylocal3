<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       27/01/2026
 */

namespace Branch8\WishlistStockAlert\Model;

use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;

class StockJoiner
{
    /**
     * @var StoreManagerInterface|StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;
    /**
     * @var StockResolverInterface
     */
    private StockResolverInterface $stockResolver;

    /**
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        StoreManagerInterface  $storeManager,
        StockResolverInterface $stockResolver
    )
    {
        $this->stockResolver = $stockResolver;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param $storeId
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Catalog\Model\ResourceModel\Product\Collection $collection, $storeId)
    {
        $store = $this->storeManager->getStore($storeId);
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $store->getWebsite()->getCode());
        $collection->getSelect()->joinLeft(
            [
                'stock' => 'branch8_options_stock_index',
            ], 'e.entity_id=stock.product_id',
        )->where('stock.stock_id = ?', $stock->getId());
        return $collection;
    }
}
