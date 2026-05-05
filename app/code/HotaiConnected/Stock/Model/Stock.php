<?php
namespace HotaiConnected\Stock\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Logger\Monolog;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Request\Http as Request;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;
use Elgentos\InventoryLog\Api\MovementRepositoryInterface;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;

class Stock
{
    // 宣告所有需要的屬性
    protected ResourceConnection $resource;
    protected Monolog $logger;
    protected Http $response;
    protected Request $request;
    protected StockRegistryInterface $stockRegistry;
    protected SourceItemRepositoryInterface $sourceItemRepository;
    protected SourceItemsSaveInterface $sourceItemsSave;
    protected SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;
    protected VariationsFactory $variationsFactory;
    protected MovementRepositoryInterface $movementRepository;
    protected GetReservationsQuantityInterface $getReservationsQuantity;

    /**
     * @param ResourceConnection                  $resource
     * @param Monolog                             $logger
     * @param Http                                $response
     * @param Request                             $request
     * @param StockRegistryInterface              $stockRegistry
     * @param SourceItemRepositoryInterface       $sourceItemRepository
     * @param SourceItemsSaveInterface            $sourceItemsSave
     * @param SearchCriteriaBuilderFactory        $searchCriteriaBuilderFactory
     * @param VariationsFactory                   $variationsFactory
     * @param MovementRepositoryInterface         $movementRepository
     * @param GetReservationsQuantityInterface    $getReservationsQuantity
     */
    public function __construct(
        ResourceConnection $resource,
        Monolog $logger,
        Http $response,
        Request $request,
        StockRegistryInterface $stockRegistry,
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceItemsSaveInterface $sourceItemsSave,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        VariationsFactory $variationsFactory,
        MovementRepositoryInterface $movementRepository,
        GetReservationsQuantityInterface $getReservationsQuantity
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
        $this->response = $response;
        $this->request = $request;
        $this->stockRegistry = $stockRegistry;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->variationsFactory = $variationsFactory;
        $this->movementRepository = $movementRepository;
        $this->getReservationsQuantity = $getReservationsQuantity;
    }

    /**
     * Generate server response
     *
     * @param  array $notFoundSkus
     * @param  bool  $success  
     * @param  int   $status
     * @return ResponseInterface
     */
    protected function serverResponse($notFoundSkus = [], $success = true, $status = 200)
    {
        $response = [
            'sku' => $notFoundSkus,
            'IsSuccess' => $success,
        ];
    
        return $this->response
            ->setHeader('Content-Type', 'application/json', true)
            ->setStatusCode($status)
            ->setBody(json_encode($response))
            ->sendResponse();
    }

    /**
     * {@inheritdoc}
     */
    public function updateStock()
    {
        try {
            $content = $this->request->getContent();
            $postData = json_decode($content, true);
            
            // 確保數據格式正確
            if (!$postData || json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->warning("[stock_update] Invalid JSON format: " . json_last_error_msg());
                $this->serverResponse([], false, 400);
                return;
            }

            // 轉換單筆資料為陣列格式
            $items = isset($postData['sku']) ? [$postData] : $postData;

            $notFoundSkus = [];
            $updateDetails = [];
            $connection = $this->resource->getConnection();

            // 批次查詢所有產品資料
            $skus = array_column($items, 'sku');
            $productDataMap = $this->batchFetchProductData($connection, $skus);

            // 批次查詢所有變體資料
            $productRowIds = array_column($productDataMap, 'row_id');
            $variationsByProductId = $this->batchFetchVariations($productRowIds);

            foreach ($items as $item) {

                $sku = $item['sku'];
                $amount = $item['amount'];

                // 從預先查詢的 map 中取得產品資料
                if (!isset($productDataMap[$sku])) {
                    $notFoundSkus[] = $sku;
                    continue;
                }

                $productData = $productDataMap[$sku];
                $productId = $productData['entity_id'];
                $productRowId = $productData['row_id'];

                // 從預先查詢的 map 中取得變體資料
                $variations = $variationsByProductId[$productRowId] ?? [];

                if (count($variations) > 0) {
                    // 有變體：直接更新所有變體庫存
                    $this->logger->info("[stock_update] Product {$sku} has " . count($variations) . " variations, updating variation stocks only");

                    $variationDetails = [];
                    foreach ($variations as $variation) {
                        $oldStock = $variation->getStock();
                        $variation->setStock($amount);
                        $variation->save();

                        $variationDetails[] = [
                            'variation_comb' => $variation->getComb(),
                            'old_stock' => $oldStock,
                            'new_stock' => $amount
                        ];
                    }

                    $updateDetails[] = [
                        'sku' => $sku,
                        'type' => 'variation_product',
                        'variation_count' => count($variations),
                        'updated_stock' => $amount,
                        'variations' => $variationDetails
                    ];

                    // 跳過一般產品的庫存更新流程
                    continue;
                }

                // 無變體：繼續原本的庫存計算流程
                // 獲取當前庫存用於記錄
                $stockItem = $this->stockRegistry->getStockItem($productId);
                $oldQty = $stockItem->getQty();

                // 獲取當前訂單預留量
                $currentReservations = $this->getReservationsQuantity->execute($sku, 1);

                // 計算實際需要設定的物理庫存 = 可銷售庫存 - 預留量
                $physicalQty = $amount - $currentReservations;

                // 若物理庫存小於0，記錄警告
                if ($physicalQty < 0) {
                    $this->logger->warning("[stock_update] Physical qty is negative: " . json_encode([
                        'sku' => $sku,
                        'incoming_qty' => $amount,
                        'original_qty' => $oldQty,
                        'reservations' => $currentReservations,
                        'calculated_physical_qty' => $physicalQty,
                        'will_be_set_to' => 0
                    ], JSON_UNESCAPED_UNICODE));
                }

                // 更新物理庫存（會自動同步到 Legacy 系統）
                $this->updateSalableQty($sku, $physicalQty);

                $updateDetails[] = [
                    'sku' => $sku,
                    'old_qty' => $oldQty,
                    'incoming_qty' => $amount,
                    'reservations' => $currentReservations,
                    'physical_qty' => max(0, $physicalQty)
                ];

                try {
                    // Update Stock Item object for logging
                    $stockItem->setOldQty($oldQty);
                    $stockItem->setQty($maxPhysicalQty = max(0, $physicalQty));
                    
                    // Log movement
                    // Note: insertStockMovement is not in the interface but is in the implementation
                    if (method_exists($this->movementRepository, 'insertStockMovement')) {
                        $this->movementRepository->insertStockMovement(
                            $stockItem, 
                            'Stock updated via HotaiConnected API'
                        );
                    }
                } catch (\Exception $e) {
                    $this->logger->error("[stock_update] Error logging movement for SKU {$sku}: " . $e->getMessage());
                }
            }

            // 整合成一個 log
            $logData = [
                'request' => $postData,
                'total_items' => count($items),
                'success_count' => count($updateDetails),
                'not_found_count' => count($notFoundSkus),
                'not_found_skus' => $notFoundSkus,
                'updates' => $updateDetails
            ];
            $this->logger->info("[stock_update] " . json_encode($logData, JSON_UNESCAPED_UNICODE));

            // 記錄未找到的SKU
            if (!empty($notFoundSkus)) {
                $this->logger->info("[stock_update] sku not match any product: " . json_encode($notFoundSkus, JSON_UNESCAPED_UNICODE));
                
                // 如果所有SKU都沒找到
                if (count($notFoundSkus) === count($items)) {
                    $this->serverResponse($notFoundSkus, false);
                    return;
                }
            }

            $this->serverResponse($notFoundSkus);
            return;

        } catch (\Exception $e) {
            $this->logger->error("[stock_update] Error: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            $this->serverResponse([], false, 500);
            return;
        }
    }

    /**
     * 更新物理庫存 (使用 Magento API - 自動觸發 Plugin 和 Indexer)
     * 注意：此方法更新的是物理庫存(inventory_source_item.quantity)
     * 可銷售庫存會由 GetProductSalableQty 即時計算：physicalQty + reservations - minQty
     *
     * @param  string $sku
     * @param  int    $physicalQty
     * @return void
     */
    protected function updateSalableQty($sku, $physicalQty)
    {
        try {
            // 確保物理庫存不為負數
            $physicalQty = max(0, $physicalQty);

            // 計算 status: 0 = Out of Stock (qty = 0), 1 = In Stock (qty > 0)
            $status = ($physicalQty > 0) ? 1 : 0;

            // 使用 Magento API 查詢該 SKU 的所有 source items
            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteria = $searchCriteriaBuilder
                ->addFilter('sku', $sku)
                ->create();

            $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();

            if (empty($sourceItems)) {
                $this->logger->warning("[stock_update] SKU {$sku} not found in inventory_source_item table");
                return;
            }

            $sourceItemsToSave = [];
            $sourceItemCount = 0;

            // 更新所有 source items
            foreach ($sourceItems as $sourceItem) {
                $sourceItem->setQuantity($physicalQty);
                $sourceItem->setStatus($status);
                $sourceItemsToSave[] = $sourceItem;
                $sourceItemCount++;
            }

            // 使用 Magento API 儲存
            // 這會自動觸發 Plugin 同步到 cataloginventory_stock_item 和觸發 indexer
            $this->sourceItemsSave->execute($sourceItemsToSave);

            $this->logger->info("[stock_update] Updated {$sourceItemCount} source items for SKU {$sku}, physical_qty: {$physicalQty}, status: " . ($status ? 'In Stock' : 'Out of Stock'));

        } catch (\Exception $e) {
            $this->logger->error("[stock_update] Error updating physical qty for SKU {$sku}: " .
                $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        }
    }

    /**
     * 批次查詢產品資料
     *
     * @param  \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param  array $skus
     * @return array 以 SKU 為 key 的產品資料陣列
     */
    protected function batchFetchProductData($connection, $skus)
    {
        if (empty($skus)) {
            return [];
        }

        try {
            $productTable = $this->resource->getTableName('catalog_product_entity');
            $select = $connection->select()
                ->from($productTable, ['sku', 'entity_id', 'row_id'])
                ->where('sku IN (?)', $skus);

            $allProductData = $connection->fetchAll($select);

            // 建立 SKU => 產品資料 的 map
            $productDataMap = [];
            foreach ($allProductData as $row) {
                $productDataMap[$row['sku']] = $row;
            }

            $this->logger->info("[stock_update] Batch fetched " . count($productDataMap) . " products from " . count($skus) . " SKUs");

            return $productDataMap;

        } catch (\Exception $e) {
            $this->logger->error("[stock_update] Error batch fetching product data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 批次查詢變體資料
     *
     * @param  array $productRowIds
     * @return array 以 product_id 為 key 的變體陣列
     */
    protected function batchFetchVariations($productRowIds)
    {
        if (empty($productRowIds)) {
            return [];
        }

        try {
            // 批次查詢所有變體
            $allVariations = $this->variationsFactory->create()
                ->getCollection()
                ->addFieldToFilter('product_id', ['in' => $productRowIds])
                ->load();

            // 建立 product_id => 變體陣列 的 map
            $variationsByProductId = [];
            foreach ($allVariations as $variation) {
                $pid = $variation->getProductId();
                if (!isset($variationsByProductId[$pid])) {
                    $variationsByProductId[$pid] = [];
                }
                $variationsByProductId[$pid][] = $variation;
            }

            $totalVariations = count($allVariations);
            $productsWithVariations = count($variationsByProductId);
            $this->logger->info("[stock_update] Batch fetched {$totalVariations} variations for {$productsWithVariations} products");

            return $variationsByProductId;

        } catch (\Exception $e) {
            $this->logger->error("[stock_update] Error batch fetching variations: " . $e->getMessage());
            return [];
        }
    }
}

