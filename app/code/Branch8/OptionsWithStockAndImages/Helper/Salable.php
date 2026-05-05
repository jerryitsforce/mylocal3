<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Magento\InventoryCatalog\Model\GetDefaultSourceItemBySku;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;
use Webkul\OptionsWithStockAndImages\Logger\Logger;
use Magento\Framework\App\ResourceConnection;
use Elgentos\InventoryLog\Helper\Data as InventoryLogHelper;
use Elgentos\InventoryLog\Api\MovementRepositoryInterface;
use Webkul\OptionsWithStockAndImages\Helper\Data as WebkulHelper;
use Branch8\SellerInformationProductDataExport\Model\Services\SyncSellerIdToIndexSellerId;
use Webkul\OptionsWithStockAndImages\Model\ResourceModel\Swatch\CollectionFactory as SwatchCollection;
use Webkul\OptionsWithStockAndImages\Model\ResourceModel\Variations\CollectionFactory as VariationsCollection;
use Webkul\OptionsWithStockAndImages\Model\SwatchFactory;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;

class Salable extends AbstractHelper
{
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Logger\Logger
     */
    public $logger;

    /**
     * Summary of productRepository
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var GetSalableQuantityDataBySku
     */
    private $getSalableQuantityDataBySku;

    /**
     * @var GetDefaultSourceItemBySku
     */
    private $getDefaultSourceItemBySku;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var GetSourceCodesBySkusInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @var GetSourceItemIds
     */
    private $getSourceItemIds;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    /**
     * @var InventoryLogHelper
     */
    public $inventoryLogHelper;

    /**
     * @var \Elgentos\InventoryLog\Api\MovementRepositoryInterface
     */
    private $movementRepository;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    public $webkulHelper;

    /**
     * @var SyncSellerIdToIndexSellerId
     */
    private $syncSellerIdToIndexSellerId;

    /**
     * @var SwatchCollection
     */
    public $swatchCollection;

    /**
     * @var VariationsCollection
     */
    public $variationCollection;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\VariationsFactory
     */
    public $variationsFactory;
    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\SwatchFactory
     */
    public $swatchFactory;

    public $cacheRangeList = [];

    /**
     * @param Context $context
     * @param StockRegistryInterface $stockRegistry
     * @param GetDefaultSourceItemBySku $getDefaultSourceItemBySku
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param GetSourceItemIds $getSourceItemIds
     * @param SourceItemIndexer $sourceItemIndexer
     * @param ProductRepositoryInterface $productRepositoryInterface
     * @param GetSalableQuantityDataBySku $getSalableQuantityDataBySku
     * @param ResourceConnection $resourceConnection
     * @param MovementRepositoryInterface $movementRepositoryInterface
     * @param InventoryLogHelper $inventoryLogHelper
     * @param WebkulHelper $webkulHelper
     * @param SyncSellerIdToIndexSellerId $syncSellerIdToIndexSellerId
     * @param SwatchCollection $swatchCollection
     * @param VariationsCollection $variationCollection
     * @param VariationsFactory $variationsFactory
     * @param SwatchFactory $swatchFactory
     * @param Logger $logger
     */
    public function __construct(
        Context                                                   $context,
        StockRegistryInterface                                    $stockRegistry,
        GetDefaultSourceItemBySku                                 $getDefaultSourceItemBySku,
        SourceItemsSaveInterface                                  $sourceItemsSave,
        GetSourceItemsBySkuInterface                              $getSourceItemsBySku,
        GetSourceItemIds                                          $getSourceItemIds,
        SourceItemIndexer                                         $sourceItemIndexer,
        ProductRepositoryInterface                                $productRepositoryInterface,
        GetSalableQuantityDataBySku                               $getSalableQuantityDataBySku,
        ResourceConnection                                        $resourceConnection,
        MovementRepositoryInterface                               $movementRepositoryInterface,
        InventoryLogHelper                                        $inventoryLogHelper,
        WebkulHelper                                              $webkulHelper,
        SyncSellerIdToIndexSellerId                               $syncSellerIdToIndexSellerId,
        SwatchCollection                                          $swatchCollection,
        VariationsCollection                                      $variationCollection,
        \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationsFactory,
        \Webkul\OptionsWithStockAndImages\Model\SwatchFactory     $swatchFactory,
        Logger                                                    $logger
    )
    {
        $this->getDefaultSourceItemBySku = $getDefaultSourceItemBySku;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->sourceItemIndexer = $sourceItemIndexer;
        $this->getSourceItemIds = $getSourceItemIds;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->stockRegistry = $stockRegistry;
        $this->productRepository = $productRepositoryInterface;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->connection = $resourceConnection->getConnection();
        $this->movementRepository = $movementRepositoryInterface;
        $this->inventoryLogHelper = $inventoryLogHelper;
        $this->webkulHelper = $webkulHelper;
        $this->syncSellerIdToIndexSellerId = $syncSellerIdToIndexSellerId;
        $this->swatchCollection = $swatchCollection;
        $this->variationCollection = $variationCollection;
        $this->variationsFactory = $variationsFactory;
        $this->swatchFactory = $swatchFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function syncNeedToRefill($ids)
    {
        $this->syncSellerIdToIndexSellerId->syncNeedToRefillIds($ids);
    }

    public function syncLivesearchInstockByProductIds($ids)
    {
        $this->syncSellerIdToIndexSellerId->syncLivesearchInstockIds($ids);
    }

    public function syncLivesearchInstock($sku)
    {
        $this->connection->beginTransaction();
        try {
            // Get product row_id for variations with is_synced = 1
            $table = $this->connection->getTableName('wk_osi_variations');
            $select = $this->connection->select()
                ->from(['variations' => $table])
                ->columns(['product_id'])
                ->where('is_sync = ?', 1)
                ->where('sku = ?', $sku);

            $ids = [];
            $cursor = $this->connection->query($select);
            while ($item = $cursor->fetch()) {
                $ids[] = $item['product_id'];
            }
            if (count($ids)) {
                // Get product entity_id from row_id
                $table_product = $this->connection->getTableName('catalog_product_entity');
                $select = $this->connection->select()
                    ->from(['product' => $table_product])
                    ->columns(['entity_id'])
                    ->where('row_id IN (?)', $ids);
                $entityIds = [];
                $cursor = $this->connection->query($select);
                while ($item = $cursor->fetch()) {
                    $entityIds[] = $item['entity_id'];
                }
                if (count($entityIds)) {
                    $this->syncSellerIdToIndexSellerId->syncLivesearchInstockIds($entityIds);
                    $this->syncSellerIdToIndexSellerId->syncNeedToRefillIds($entityIds);
                }
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function syncStockForVariations($sku, $qty)
    {
        $this->connection->beginTransaction();
        try {
            $updateData = [
                'stock' => $qty
            ];
            $whereUpdate = [
                'is_sync = ?' => 1,
                'sku = ?' => $sku
            ];
            $this->connection->update(
                'wk_osi_variations',
                $updateData,
                $whereUpdate
            );
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function getReservedQuantityBySku(string $sku): int
    {
        $tableName = $this->connection->getTableName('inventory_reservation');

        $select = $this->connection->select()
            ->from(['ir' => $tableName], [])
            ->columns(['reserved_qty' => new \Zend_Db_Expr('SUM(ir.quantity)')]) // Sum the quantity
            ->where('ir.sku = ?', $sku)
            ->group('ir.sku');

        $reservedQty = $this->connection->fetchOne($select);

        return $reservedQty !== false ? (int)$reservedQty : 0;
    }

    public function saveStock($sku, $qty)
    {
        $inventoryData = [
            StockItemInterface::QTY => $qty
        ];
        $sourceItems = $this->getDefaultSourceItems([$sku], $inventoryData);
        if ($sourceItems) {
            try {
                $this->sourceItemsSave->execute($sourceItems);
            } catch (CouldNotSaveException|InputException|ValidationException $e) {
                $this->logger->error($e->getLogMessage());
            }
        }

        $this->reindexSourceItems([$sku]);

        $this->logger->info(
            sprintf(
                'Stock updated for SKU: %s, Quantity: %d',
                $sku,
                $qty
            )
        );

        $reserved_quantity = $this->getReservedQuantityBySku($sku);
        $salableQty = $qty + $reserved_quantity;

        if ($salableQty < 0) {
            $salableQty = 0;
        }

        $this->logger->info(
            sprintf(
                'Salable quantity updated for SKU: %s, Salable Quantity: %d',
                $sku,
                $salableQty
            )
        );

        $this->syncStockForVariations($sku, $salableQty);

        return $salableQty;
    }

    public function getProductStockItem($productId)
    {
        $stockItem = $this->stockRegistry->getStockItem($productId);
        if ($stockItem->getId()) {
            return $stockItem;
        }
        return false;
    }

    public function saveStockMovementLog($stockItem, $oldQty, $newQty, $message)
    {
        if ($this->inventoryLogHelper->isModuleEnabled()) {
            $stockItem->setOldQty((int)$oldQty);
            $stockItem->setQty((int)$newQty);
            $this->movementRepository->insertStockMovement(
                $stockItem,
                $message
            );
            $this->inventoryLogHelper->unRegisterAllData();
        }
    }

    public function backStockQty($sku, $qtyOrdered, $order = null, $message = '')
    {
        $stockItem = $this->stockRegistry->getStockItemBySku($sku);
        $qty = (int)$stockItem->getQty() + $qtyOrdered;
        $this->saveStockMovementLog($stockItem, $stockItem->getQty(), $qty, $message);
        return $this->saveStock($sku, $qty);
    }

    public function subtractStockQty($sku, $qtyOrdered, $order = null, $message = '')
    {
        $stockItem = $this->stockRegistry->getStockItemBySku($sku);
        $qty = (int)$stockItem->getQty() - $qtyOrdered;
        $this->saveStockMovementLog($stockItem, $stockItem->getQty(), $qty, $message);
        return $this->saveStock($sku, $qty);
    }

    public function getCurrentProductRowId($productId)
    {
        $product = $this->productRepository->getById($productId);
        if ($product->getId() && $product->getRowId()) {
            return $product->getRowId();
        }
        return null;
    }

    public function getQtyBySku($sku)
    {
        $qty = 0;
        $stock = null;
        $product = $this->productRepository->get(trim($sku));
        if ($product->getId() && $product->getTypeId() == 'simple') {
            $salable = $this->getSalableQuantityDataBySku->execute(trim($product->getSku()));
            $stock = $this->stockRegistry->getStockItem($product->getId());

            $reserved_quantity = $this->getReservedQuantityBySku(trim($sku));
            $salableQty = $stock->getQty() + $reserved_quantity;
            if ($salableQty < 0) {
                $salableQty = 0;
            }

            if ($stock->getIsInStock() && (int)$product->getStatus() !== Status::STATUS_DISABLED) {
                if (isset($salable[0]['qty'])) {
                    $qty = $salable[0]['qty'];
                } else {
                    $qty = $stock->getQty();
                }
            }

            $qty = max($salableQty, $qty);
        }

        return $qty;
    }

    /**
     * Get default source items for given product skus.
     *
     * @param array $skus
     * @param array $inventoryData
     * @return array
     */
    private function getDefaultSourceItems(array $skus, array $inventoryData): array
    {
        $sourceItems = [];
        foreach ($skus as $sku) {
            $sourceItem = $this->getDefaultSourceItemBySku->execute($sku);
            if ($sourceItem) {
                $qty = $inventoryData[StockItemInterface::QTY] ?? $sourceItem->getQuantity();
                $status = $inventoryData[StockItemInterface::IS_IN_STOCK] ?? $sourceItem->getStatus();
                $sourceItem->setQuantity((float)$qty);
                $sourceItem->setStatus((int)$status);
                $sourceItems[] = $sourceItem;
            }
        }

        return $sourceItems;
    }

    /**
     * Reindex non-default source items.
     *
     * @param array $skus
     */
    public function reindexSourceItems(array $skus): void
    {
        $sourceItems = [[]];
        foreach ($skus as $sku) {
            $sourceItems[] = $this->getSourceItemsBySku->execute($sku);
        }
        $sourceItems = array_merge(...$sourceItems);
        $sourceItemsIds = $this->getSourceItemIds->execute($sourceItems);
        $this->sourceItemIndexer->executeList($sourceItemsIds);
    }

    /**
     * Check isOWSIProduct or not
     *
     * @return int
     */
    protected function isOWSIProduct($product)
    {
        $row_ids = $this->getRowId($product);
        $count = 0;
        if (count($row_ids)) {
            $collection = $this->variationsFactory->create()
                ->getCollection()
                ->addFieldToFilter("product_id", ['in' => $row_ids]);
            $count = $collection->getSize();
        }
        return $count;
    }

    protected function getRowId($product)
    {
        $table = $this->connection->getTableName('catalog_product_entity');
        $where = $this->connection->quoteInto('entity_id = ?', $product->getId());
        return $this->connection->fetchCol("SELECT `{$table}`.`row_id` FROM `{$table}` WHERE {$where}");
    }

    public function changeCostAndPriceForVariation($sku)
    {
        $product = $this->productRepository->get(trim($sku));
        if (!$product->getId() || !$this->isOWSIProduct($product)) {
            return;
        }
        $this->logger->info("changeCostAndPriceForVariation-ProductRowId:" . $product->getRowId());

        // Update price if follow_simple_sku_price_setting = 1
        $priceSelect = $this->variationCollection->create()
            ->addFieldToFilter("product_id", $product->getRowId())
            ->addFieldToFilter("follow_simple_sku_price_setting", 1)
            ->getFirstItem();
        if ($priceSelect->getId()) {
            $updateData = [
                'price' => $product->getFinalPrice(),
                'weight' => $product->getWeight()
            ];
            $whereUpdate = [
                'product_id = ?' => $product->getRowId()
            ];
            $this->connection->beginTransaction();
            try {
                $this->connection->update(
                    'wk_osi_variations',
                    $updateData,
                    $whereUpdate
                );
                $this->connection->commit();
            } catch (\Exception $e) {
                $this->connection->rollBack();
                throw $e;
            }
        }

        $costSelect = $this->variationCollection->create()
            ->addFieldToFilter("product_id", $product->getRowId())
            ->addFieldToFilter("follow_simple_sku_cost_setting", 1)
            ->getFirstItem();
        if ($costSelect->getId()) {
            $allVariations = $this->variationCollection->create()
                ->addFieldToFilter("product_id", $product->getRowId())
                ->getItems();
            $this->logger->info("COUNT:" . count($allVariations));
            foreach ($allVariations as $variation) {
                $this->logger->info(json_encode($variation->getData()));
                $price = $variation->getData('price') ?? $product->getFinalPrice();
                $cost = $product->getData('cost');
                $commissionPercent = $product->getData('commission_percent');
                $costSetting = $product->getData('cost_setting');
                list($cost, $commissionPercent) = $this->calculateProductCost($price, $costSetting, $commissionPercent, $cost);
                $variation->setCost($cost);
                $variation->setCostSetting($costSetting);
                $variation->setCommissionPercent($commissionPercent);
                $variation->setFollowSimpleSkuCostSetting(1);
                $variation->setDataChanges(true)->save();
            }
        }
        $this->logger->info("===============ENDDEBUG=================");
    }

    public function changeCostForVariation($product)
    {
        $this->changeCostAndPriceForVariation($product->getSku());
    }

    public function changeCostContractRollover($product_row_id, $finalPrice, $commissionPercent, $cost)
    {
        $wk_manage_variation_before = [];
        $wk_manage_variation_after = [];
        $allVariations = $this->variationCollection->create()
            ->addFieldToFilter("product_id", $product_row_id)
            ->addFieldToFilter("follow_simple_sku_cost_setting", 1)
            ->addFieldToFilter("cost_setting", 1) // 1 = Percentage | Fixed commission
            ->getItems();
        foreach ($allVariations as $variation) {
            $wk_manage_variation_before[] = [
                'comb' => $variation->getData('comb'),
                'cost' => $variation->getData('cost'),
                'commission_percent' => $variation->getData('commission_percent')
            ];
            $price = $variation->getData('price') ?? $finalPrice;
            list($variationCost, $variationCommissionPercent) = $this->calculateProductCost($price, 1, $commissionPercent, $cost);
            if ($variationCost != $variation->getData('cost') || $variationCommissionPercent != $variation->getData('commission_percent')) {
                $variation->setCost($variationCost);
                $variation->setCommissionPercent($variationCommissionPercent);
                $variation->save();
                $wk_manage_variation_after[] = [
                    'comb' => $variation->getData('comb'),
                    'cost' => $variation->getData('cost'),
                    'commission_percent' => $variation->getData('commission_percent')
                ];
            }
        }
        return [$wk_manage_variation_before, $wk_manage_variation_after];
    }

    public function changeManageStock($product)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId());
        if ($this->isOWSIProduct($product)) {
            $stockItem->setUseConfigManageStock(0);
            $stockItem->setManageStock(0);
            $stockItem->setQty(0);
            $stockItem->save();
        } else {
            $stockItem->setUseConfigManageStock(1);
            $stockItem->setManageStock(1);
            $stockItem->save();
        }
    }

    public function combineArrays($arrayOfArrays)
    {
        $odometer = array_fill(0, count($arrayOfArrays), 0);
        $output = [];
        $newCombination = $this->formCombination($odometer, $arrayOfArrays);

        if (in_array($newCombination, $output)) {
            return [];
        }

        $output[] = $newCombination;

        while ($this->odometerIncrement($odometer, $arrayOfArrays)) {
            $newCombination = $this->formCombination($odometer, $arrayOfArrays);

            if (in_array($newCombination, $output)) {
                return [];
            }
            $lastChar = substr($newCombination, -1);
            if ($lastChar === '_') {
                $newCombination = substr($newCombination, 0, -1);
            }
            $output[] = $newCombination;
        }

        return $output;
    }

    protected function formCombination($odometer, $array_of_arrays)
    {
        return $this->array_reduce_assoc(
            $odometer,
            function ($accumulator, $odometer_key, $odometer_value) use ($array_of_arrays) {
                if (isset($array_of_arrays[$odometer_key][$odometer_value])) {
                    return "" . $accumulator . $array_of_arrays[$odometer_key][$odometer_value] . '_';
                } else {
                    return "" . $accumulator . 0 . '_';
                }
            },
            ""
        );
    }

    protected function array_reduce_assoc(array $array, callable $callback, $initial = null)
    {
        $carry = $initial;
        foreach ($array as $key => $value) {
            $carry = $callback($carry, $key, $value);
        }
        return $carry;
    }

    protected function odometerIncrement(&$odometer, $arrayOfArrays)
    {
        for ($iOdometerDigit = count($odometer) - 1; $iOdometerDigit >= 0; $iOdometerDigit--) {
            $maxee = count($arrayOfArrays[$iOdometerDigit]) - 1;

            if ($odometer[$iOdometerDigit] + 1 <= $maxee) {
                $odometer[$iOdometerDigit]++;
                return true;
            } else {
                if ($iOdometerDigit - 1 < 0) {
                    return false;
                } else {
                    $odometer[$iOdometerDigit] = 0;
                    continue;
                }
            }
        }
    }

    /**
     * @param $requestQty
     * @param $productRowId
     * @param $combo
     * @return bool
     * @throws \Exception
     */
    public function checkStockAvailabilityForCombo($requestQty, $productRowId, $combo)
    {
        /**
         * @var $variation \Webkul\OptionsWithStockAndImages\Model\Variations
         */
        $variation = $this->webkulHelper->getCombData($productRowId, $combo);
        if (!$variation->getId()) {
            return false;
        }
        try {
            $stockToCheck = (int)$variation->getData('stock');
            $realTimeStock = $this->getVariationStockForUpdate($productRowId, $combo);
            if ($realTimeStock !== false) {
                $stockToCheck = (int)$realTimeStock;
            }
            if ($stockToCheck - $requestQty < 0) {
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->critical($e->getTraceAsString());
            throw $e;
        }
        return true;
    }

    /**
     * Check Availability
     *
     * @param string $itemOptions
     * @param array $productCombArr
     * @param array $optionData
     * @param string $comb
     * @param int $productId
     * @param \Magento\Checkout\Model\Cart $item
     *
     * @return array $notAvailableArr
     */
    public function checkAvailability($itemOptions, $productCombArr, $optionData, $comb, $productId, $item)
    {
        $comb = "";
        $notAvailableArr = [];
        $options = json_decode($itemOptions);
        if (isset($options->options)) {
            foreach ($options->options as $key => $value) {
                if (isset($optionData[$key]) && isset($optionData[$key][$value])) {
                    $comb .= $optionData[$key][$value] . "_";
                }
            }
            $comb = trim($comb, "_");
            $variation = $this->webkulHelper->getCombData($productId, $comb);
            if ($variation->getId()) {
                if (isset($productCombArr[$productId][$comb])) {
                    $productCombArr[$productId][$comb] += $item->getQty();
                } else {
                    $productCombArr[$productId][$comb] = $item->getQty();
                }

                $stockToCheck = $variation->getStock();
                try {
                    $realTimeStock = $this->getVariationStockForUpdate($productId, $comb);
                    if ($realTimeStock !== false) {
                        $stockToCheck = (int)$realTimeStock;
                    }
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                    throw $e;
                }

                if (($stockToCheck - $productCombArr[$productId][$comb]) < 0) {
                    $notAvailableArr[] = $item->getName() . " (" . $comb . ")";
                    $productCombArr[$productId][$comb] -= $item->getQty();
                }
            }
        }
        return $notAvailableArr;
    }

    /**
     * Get Variation Stock with Lock (FOR UPDATE)
     *
     * @param int $productId
     * @param string $comb
     * @return string|bool
     */
    public function getVariationStockForUpdate($productId, $comb)
    {
        $table = $this->connection->getTableName('wk_osi_variations');
        $select = $this->connection->select()
            ->from($table, ['stock'])
            ->where('product_id = ?', $productId)
            ->where('comb = ?', $comb)
            ->forUpdate(true);

        return $this->connection->fetchOne($select);
    }

    public function getMessageLog($item, $order)
    {
        $msg[] = __('order: %1', $order->getIncrementId());
        $result = $this->getCustomOptionLabelsAndVariation($item);
        if (count($result['custom_option_labels']) > 0) {
            $msgCO = [];
            foreach ($result['custom_option_labels'] as $label) {
                $msgCO[] = $label['label'] . ' : ' . $label['value'];
            }
            if (count($msgCO) > 0) {
                $msg[] = __('Custom option: %1', implode(' , ', $msgCO));
            }
        }
        if (!empty($result['variation'])) {
            $msg[] = __('Variation: %1', $result['variation']);
        }
        return implode(' - ', $msg);
    }

    /**
     * Get custom option labels and variation data from a sales order item.
     *
     * @param OrderItemInterface $orderItem
     * @return array
     */
    public function getCustomOptionLabelsAndVariation($orderItem): array
    {
        $result = [
            'custom_option_labels' => [],
            'variation' => null,
        ];
        $comb = "";
        $product = $orderItem->getProduct();
        $productRowId = $product->getRowId();
        $optionData = [];
        foreach ($product->getOptions() as $option) {
            $optType = $option->getType();
            if ($optType == "drop-down" || $optType == "drop_down" || $optType == "radio") {
                $optionId = $option->getId();
                $optionData[$optionId] = [];
                foreach ($option->getValues() as $value) {
                    $valueId = $value->getId();
                    $optionData[$optionId][$valueId] = $value->getDefaultTitle();
                }
            }
        }
        if (!empty($optionData)) {
            $options = $orderItem->getProductOptions();
            if (isset($options['options']) && !empty($options['options'])) {
                foreach ($options['options'] as $option) {
                    if (!isset($optionData[$option['option_id']])) {
                        continue;
                    }
                    $optDataArr = $optionData[$option['option_id']];
                    if (isset($optDataArr) && isset($optDataArr[$option['option_value']])) {
                        $comb .= $optDataArr[$option['option_value']] . "_";
                        $result['custom_option_labels'][] = [
                            'label' => $option['label'],
                            'value' => $option['value'],
                        ];
                    }
                }
            }
            $comb = trim($comb, "_");
            $variation = $this->webkulHelper->getCombData($productRowId, $comb);
            if ($variation->getId()) {
                $result['variation'] = $variation->getComb();
            }
        }
        return $result;
    }

    public function getVariation($productRowId, $comb)
    {
        return $this->webkulHelper->getCombData($productRowId, $comb);
    }

    public function calculateProductCost($finalPrice, $cost_setting, $commission_percent, $cost)
    {
        if ($cost_setting == '1' || $cost_setting == 1) {
            if (empty($commission_percent)) {
                $commission_percent = 0;
            }
            $cost = round((100 - $commission_percent) / 100 * $finalPrice);
        } elseif ($cost_setting == '0' || $cost_setting == 0) {
            if (empty($cost)) {
                $cost = 0;
            }
            $commissionPercent = ($finalPrice - $cost) / $finalPrice * 100;
            if ($commissionPercent > 0)
                $commissionPercent = round($commissionPercent);
            $commission_percent = $commissionPercent;
        }
        return [$cost, $commission_percent];
    }

    public function saveVariationAndSwatchesForDuplicateProduct($originalProduct, $duplicateProduct)
    {
        $originalProductRowId = $originalProduct->getRowId();
        $originalProductId = $originalProduct->getId();
        $duplicateProductRowId = $duplicateProduct->getRowId();
        $swatchesCollection = $this->swatchCollection->create()->addFieldToFilter('product_id', $originalProductRowId);
        $variationsCollection = $this->variationCollection->create()->addFieldToFilter('product_id', $originalProductRowId);
        if ($swatchesCollection->getSize() != 0 || $variationsCollection->getSize() != 0) {
            try {
                // saving variations for duplicate product
                foreach ($variationsCollection as $variation) {
                    $variationFactory = $this->variationsFactory->create();
                    $variationFactory->setWeight($variation->getWeight());
                    $variationFactory->setComb($variation->getComb());
                    $variationFactory->setImage($variation->getImage());
                    $variationFactory->setStock($variation->getStock());
                    $variationFactory->setProductId($duplicateProductRowId);
                    $variationFactory->setmageProductId($originalProductId);
                    $variationFactory->setSku($variation->getSku());
                    $variationFactory->setIsSync($variation->getIsSync());
                    $variationFactory->setIsLockSku($variation->getIsLockSku());
                    $variationFactory->setProductItemId($variation->getProductItemId());
                    $variationFactory->save();
                }
                foreach ($swatchesCollection as $swatch) {
                    $swatchesFactory = $this->swatchFactory->create();
                    $swatchesFactory->setOptionId($swatch->getOptionId());
                    $swatchesFactory->setTitle($swatch->getTitle());
                    $swatchesFactory->setIsSwatch($swatch->getIsSwatch());
                    $swatchesFactory->setProductId($duplicateProductRowId);
                    $swatchesFactory->save();
                }

                $stockItem = $this->stockRegistry->getStockItem($duplicateProduct->getId());
                $stockItem->setUseConfigManageStock(0);
                $stockItem->setManageStock(0);
                $stockItem->setQty(0);
                $stockItem->save();

            } catch (\Exception $e) {
            }
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get column range from A to given end column with caching.
     *
     * @param string $endColumn
     * @param string $firstLetter
     * @return array
     */
    public function getRangeList($endColumn = 'ZZ', $firstLetter = ''): array
    {
        if (isset($this->cacheRangeList[$endColumn . '_' . $firstLetter])) {
            return $this->cacheRangeList[$endColumn . '_' . $firstLetter];
        }
        $this->cacheRangeList[$endColumn . '_' . $firstLetter] = $this->getRange($endColumn, $firstLetter);
        return $this->cacheRangeList[$endColumn . '_' . $firstLetter];
    }

    /**
     * Generate column range from A to given end column.
     *
     * @param string $endColumn
     * @param string $firstLetter
     * @return array
     */
    private function getRange($endColumn = 'ZZ', $firstLetter = ''): array
    {
        $columns = array();
        $length = strlen($endColumn);
        $letters = range('A', 'Z');

        foreach ($letters as $letter) {
            $column = $firstLetter . $letter;
            $columns[] = $column;
            if ($column == $endColumn) {
                return $columns;
            }
        }

        foreach ($columns as $column) {
            if (!in_array($endColumn, $columns) && strlen($column) < $length) {
                $newColumns = $this->getRange($endColumn, $column);
                $columns = array_merge($columns, $newColumns);
            }
        }
        return $columns;
    }
}
