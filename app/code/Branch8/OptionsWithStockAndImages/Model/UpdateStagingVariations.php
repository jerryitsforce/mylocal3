<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Model;

use Branch8\SellerInformationProductDataExport\Model\Services\SyncSellerIdToIndexSellerId;
use Magento\Staging\Model\VersionHistoryInterface;
use Magento\Framework\App\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogStaging\Model\Product\Retriever as ProductRetriever;
use Magento\Staging\Model\StagingApplier\PostProcessorInterface;
use Magento\Staging\Model\Entity\RetrieverPool;
use Magento\Framework\App\ResourceConnection;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;
use Webkul\OptionsWithStockAndImages\Model\ResourceModel\Variations\CollectionFactory as VariationsCollection;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class UpdateStagingVariations implements PostProcessorInterface
{
    /**
     * @var VersionHistoryInterface
     */
    private $versionHistory;

    /**
     * @var Config
     */
    private $scopeConfigCache;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    /**
     * @var RetrieverPool
     */
    private $retrieverPool;

    /**
     * @var VariationsFactory
     */
    protected $variationsFactory;

    /**
     * @var VariationsCollection
     */
    public $variationCollection;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var SyncSellerIdToIndexSellerId
     */
    protected SyncSellerIdToIndexSellerId $service;

    /**
     * @param VersionHistoryInterface $versionHistory
     * @param Config $scopeConfigCache
     * @param ProductRepositoryInterface $productRepository
     * @param ResourceConnection $resourceConnection
     * @param StockRegistryInterface $stockRegistry
     * @param VariationsFactory $variationsFactory
     * @param RetrieverPool $retrieverPool
     * @param SyncSellerIdToIndexSellerId $service
     */
    public function __construct(
        VersionHistoryInterface $versionHistory,
        Config $scopeConfigCache,
        ProductRepositoryInterface $productRepository,
        ResourceConnection $resourceConnection,
        StockRegistryInterface $stockRegistry,
        VariationsFactory $variationsFactory,
        VariationsCollection $variationCollection,
        RetrieverPool $retrieverPool,
        SyncSellerIdToIndexSellerId $service
    ) {
        $this->versionHistory = $versionHistory;
        $this->scopeConfigCache = $scopeConfigCache;
        $this->productRepository = $productRepository;
        $this->connection = $resourceConnection->getConnection();
        $this->stockRegistry = $stockRegistry;
        $this->variationsFactory = $variationsFactory;
        $this->variationCollection = $variationCollection;
        $this->retrieverPool = $retrieverPool;
        $this->service = $service;
    }

    /**
     * @param int $oldVersionId
     * @param int $currentVersionId
     * @param array $entityIds
     * @param string $entityType
     */
    public function execute(
        int $oldVersionId,
        int $currentVersionId,
        array $entityIds,
        string $entityType
    ): void {
        if ($this->retrieverPool->getRetriever($entityType) instanceof ProductRetriever) {
            foreach ($entityIds as $entityId) {
                $newProduct = $this->productRepository->getById($entityId, false, null, true);
                $newRowId = $newProduct->getRowId();
                $newOptions = $newProduct->getOptions();
                $this->versionHistory->setCurrentId($oldVersionId);
                $this->scopeConfigCache->clean();
                $product = $this->productRepository->getById($entityId, false, null, true);
                $oldRowId = $product->getRowId();
                $oldOptions = $product->getOptions();
                $this->versionHistory->setCurrentId($currentVersionId);
                $this->scopeConfigCache->clean();
                // Update price if follow_simple_sku_price_setting = 1
                $priceSelect = $this->variationCollection->create()
                    ->addFieldToFilter("product_id", $product->getRowId())
                    ->addFieldToFilter("follow_simple_sku_price_setting", 1)
                    ->getFirstItem();
                $updateData = ['product_id' => $newRowId];
                if($priceSelect->getId()){
                    $updateData['price'] = $newProduct->getFinalPrice();
                    $updateData['weight'] = $newProduct->getWeight();
                }

                $costSelect = $this->variationCollection->create()
                    ->addFieldToFilter("product_id", $product->getRowId())
                    ->addFieldToFilter("follow_simple_sku_cost_setting", 1)
                    ->getFirstItem();
                if($costSelect->getId()){
                    $price = $newProduct->getFinalPrice();
                    $cost = $newProduct->getData('cost');
                    $commissionPercent = $newProduct->getData('commission_percent');
                    $costSetting = $newProduct->getData('cost_setting');
                    list($cost, $commissionPercent) = $this->calculateProductCost($price, $costSetting, $commissionPercent, $cost);
                    $updateData['cost'] = $cost;
                    $updateData['cost_setting'] = $costSetting;
                    $updateData['commission_percent'] = $commissionPercent;
                }
                $this->updateProductId($oldRowId, $newRowId, $updateData, $oldOptions, $newOptions);
                if($this->isOWSIProduct($newRowId)){
                    $stockItem = $this->stockRegistry->getStockItem($entityId);
                    $stockItem->setUseConfigManageStock(0);
                    $stockItem->setManageStock(0);
                    $stockItem->setQty(0);
                    $stockItem->save();
                    $this->service->syncLivesearchInstockIds([$entityId]);
                    $this->service->syncNeedToRefillIds([$entityId]);
                }
            }
        }
    }

    protected function updateProductId($old_id, $new_id, $updateData, $oldOptions, $newOptions){
        try{
            $whereUpdate = [
                'product_id = ?' => $old_id
            ];
            $this->connection->update(
                'wk_osi_variations',
                $updateData,
                $whereUpdate
            );
            if (!empty($oldOptions) && !empty($newOptions)) {
                $oldOptionData = [];
                $newOptionData = [];
                foreach ($oldOptions as $option) {
                    $otpType = $option->getType();
                    if ($otpType == "drop-down" || $otpType == "drop_down" || $otpType == "radio") {
                        $oldOptionData[trim($option->getTitle())] = $option->getOptionId();
                    }
                }
                foreach ($newOptions as $option) {
                    $otpType = $option->getType();
                    if ($otpType == "drop-down" || $otpType == "drop_down" || $otpType == "radio") {
                        $newOptionData[trim($option->getTitle())] = $option->getOptionId();
                    }
                }
                if (!empty($oldOptionData)) {
                    foreach ($oldOptionData as $title => $optionId) {
                        if (isset($newOptionData[$title])) {
                            $updateData = [
                                'option_id' => $newOptionData[$title],
                                'product_id' => $new_id
                            ];
                            $whereUpdate = [
                                'option_id = ?' => $optionId,
                                'product_id = ?' => $old_id
                            ];
                            $this->connection->update(
                                'wk_osi_swatch',
                                $updateData,
                                $whereUpdate
                            );
                        }
                    }
                } else {
                    $this->connection->update(
                        'wk_osi_swatch',
                        $updateData,
                        $whereUpdate
                    );
                }
            } else {
                $this->connection->update(
                    'wk_osi_swatch',
                    $updateData,
                    $whereUpdate
                );
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Check isOWSIProduct or not
     *
     * @return int
     */
    protected function isOWSIProduct($productId)
    {
        $count = 0;
        if ($productId) {
            $collection = $this->variationsFactory->create()
                            ->getCollection()
                            ->addFieldToFilter("product_id", $productId);
            $count = $collection->getSize();
        }
        return $count;
    }

    public function calculateProductCost($finalPrice, $cost_setting, $commission_percent, $cost)
    {
        if ($cost_setting == '1' || $cost_setting == 1) {
            if(empty($commission_percent)){
                $commission_percent = 0;
            }
            $cost = round((100 - $commission_percent) / 100 * $finalPrice);
        } elseif ($cost_setting == '0' || $cost_setting == 0) {
            if(empty($cost)){
                $cost = 0;
            }
            $commissionPercent = ($finalPrice - $cost) / $finalPrice * 100;
            if ($commissionPercent > 0)
                $commissionPercent = round($commissionPercent);
            $commission_percent = $commissionPercent;
        }
        return [$cost, $commission_percent];
    }

}
