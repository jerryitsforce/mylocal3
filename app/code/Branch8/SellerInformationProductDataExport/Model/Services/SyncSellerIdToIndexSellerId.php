<?php
declare(strict_types=1);

namespace Branch8\SellerInformationProductDataExport\Model\Services;

use Branch8\GA4\Model\ProductHelper;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class SyncSellerIdToIndexSellerId
{
    const INDEX_SELLER_ATTRIBUTE = 'index_seller_id';
    const LIVESEARCH_CATEGORIES = 'livesearch_categories';
    const LIVESEARCH_INSTOCK = 'livesearch_instock';
    const SELLER_SHOP_NAME = 'seller_shop_name';
    const MAIN_CATEGORY = 'main_category';
    const NEED_TO_REFILL = 'need_to_refill';
    const INDEX_STOCK_STATUS = 'index_stock_status';
    const BATCH_SIZE = 1000;

    private ResourceConnection $resourceConnection;
    private Config $config;
    private LoggerInterface $logger;
    private ProductHelper $productHelper;

    /**
     * @var array
     */
    private array $categoriesCache = [];

    /**
     * @var array
     */
    private array $attributeCache = [];

    /**
     * @param ResourceConnection $resourceConnection
     * @param Config $config
     * @param LoggerInterface $logger
     * @param ProductHelper $productHelper
     */
    private $marketplacePreorderHelper;
    private $timezone;
    private $productCollectionFactory;

    /**
     * @param ResourceConnection $resourceConnection
     * @param Config $config
     * @param LoggerInterface $logger
     * @param ProductHelper $productHelper
     * @param \Webkul\MarketplacePreorder\Helper\Data $marketplacePreorderHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Config             $config,
        LoggerInterface    $logger,
        ProductHelper      $productHelper,
        \Webkul\MarketplacePreorder\Helper\Data $marketplacePreorderHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    ) {
        $this->config = $config;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->productHelper = $productHelper;
        $this->marketplacePreorderHelper = $marketplacePreorderHelper;
        $this->timezone = $timezone;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param string $code
     * @return AbstractAttribute
     * @throws LocalizedException
     */
    private function getAttribute(string $code): AbstractAttribute
    {
        if (!isset($this->attributeCache[$code])) {
            $attribute = $this->config->getAttribute(Product::ENTITY, $code);
            if (!$attribute || !$attribute->getId()) {
                throw new LocalizedException(__('No attribute found: %1', $code));
            }
            $this->attributeCache[$code] = $attribute;
        }
        return $this->attributeCache[$code];
    }

    private function getProductEntityTable(): string
    {
        return $this->resourceConnection->getTableName('catalog_product_entity');
    }

    /**
     * Resolve incoming IDs (which could be entity_id or row_id) to unique entity_ids.
     * This is crucial because mview triggers might send row_id (e.g. from wk_osi_variations),
     * but we generally want to query by entity_id to be safe and consistent.
     *
     * @param array $ids
     * @return array
     */
    private function resolveEntityIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $resolvedIds = [];
        $connection = $this->resourceConnection->getConnection();
        $table = $this->getProductEntityTable();

        // Separate IDs into numeric (potential entity_id/row_id) and strings (potential SKUs)
        $numericIds = [];
        $skus = [];
        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $numericIds[] = $id;
            } else {
                $skus[] = $id;
            }
        }

        // Process Numeric IDs
        if (!empty($numericIds)) {
            $chunks = array_chunk($numericIds, self::BATCH_SIZE);
            foreach ($chunks as $chunk) {
                $select = $connection->select()
                    ->from($table, 'entity_id')
                    ->where('entity_id IN (?)', $chunk)
                    ->orWhere('row_id IN (?)', $chunk);

                $fetched = $connection->fetchCol($select);
                foreach ($fetched as $fid) {
                    $resolvedIds[$fid] = $fid;
                }
            }
        }

        // Process SKUs
        if (!empty($skus)) {
            $chunks = array_chunk($skus, self::BATCH_SIZE);
            foreach ($chunks as $chunk) {
                $select = $connection->select()
                    ->from($table, 'entity_id')
                    ->where('sku IN (?)', $chunk);

                $fetched = $connection->fetchCol($select);
                foreach ($fetched as $fid) {
                    $resolvedIds[$fid] = $fid;
                }
            }
        }

        return array_values($resolvedIds);
    }

    /**
     * @param array $ids
     * @return bool
     */
    public function syncLivesearchInstockIds(array $ids): bool
    {
        if (empty($ids)) {
            return true;
        }

        $ids = $this->resolveEntityIds($ids);
        if (empty($ids)) {
            return true;
        }

        $chunks = array_chunk($ids, self::BATCH_SIZE);
        foreach ($chunks as $chunk) {
            $this->processLivesearchInstockChunk($chunk);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function syncLivesearchInstockAll(): bool
    {
        foreach ($this->getProductIdBatch() as $batchIds) {
            $this->processLivesearchInstockChunk($batchIds);
        }
        return true;
    }

    private function processLivesearchInstockChunk(array $ids): void
    {
        try {
            $attribute = $this->getAttribute(self::LIVESEARCH_INSTOCK);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('catalog_product_entity_int');

        // 1. Sync from Stock Item
        $select = $connection->select()
            ->from(['stock' => $connection->getTableName('cataloginventory_stock_item')], ['is_in_stock'])
            ->join(
                ['p' => $this->getProductEntityTable()],
                'p.entity_id = stock.product_id',
                ['product_row_id' => 'p.row_id']
            )
            ->where('p.entity_id IN (?)', $ids);

        $data = [];
        $cursor = $connection->query($select);
        while ($item = $cursor->fetch()) {
            $data[] = [
                'store_id' => 0,
                'row_id' => $item['product_row_id'],
                'attribute_id' => $attribute->getId(),
                'value' => $item['is_in_stock']
            ];
        }

        if (!empty($data)) {
            $connection->insertOnDuplicate($table, $data);
        }

        // 2. Sync from OSI Variations (Optimized PHP Logic)
        $this->processVariationsStockLogic($ids, $attribute, $table, false);
    }

    /**
     * Logic to handle wk_osi_variations updates for both Instock and Refill
     * @param array $ids
     * @param AbstractAttribute $attribute
     * @param string $tableName
     * @param bool $isRefillLogic
     */
    private function processVariationsStockLogic(array $ids, AbstractAttribute $attribute, string $tableName, bool $isRefillLogic): void
    {
        $connection = $this->resourceConnection->getConnection();

        // Fetch Variations
        // Note: Joining p.row_id = var.product_id because wk_osi_variations typically links to row_id on EE/Staging
        $selectVars = $connection->select()
            ->from(['var' => $connection->getTableName('wk_osi_variations')], ['product_id', 'comb', 'stock'])
            ->join(
                ['p' => $this->getProductEntityTable()],
                'p.row_id = var.product_id',
                ['row_id']
            )
            ->where('p.entity_id IN (?)', $ids);

        $variations = $connection->fetchAll($selectVars);
        if (empty($variations)) {
            return;
        }

        // Fetch Option Visibility
        $selectOptions = $connection->select()
            ->from(['p' => $this->getProductEntityTable()], ['row_id'])
            ->join(['op' => $connection->getTableName('catalog_product_option')], 'p.row_id = op.product_id', [])
            ->join(['opval' => $connection->getTableName('catalog_product_option_type_value')], 'op.option_id = opval.option_id', ['is_visible'])
            ->join(['optit' => $connection->getTableName('catalog_product_option_type_title')], 'opval.option_type_id = optit.option_type_id', ['title'])
            ->where('optit.store_id = 0')
            ->where('p.entity_id IN (?)', $ids);

        $options = $connection->fetchAll($selectOptions);

        // Organize Options: row_id -> title -> is_visible
        $productOptions = [];
        foreach ($options as $opt) {
            $rowId = $opt['row_id'];
            $title = trim((string)$opt['title']);
            if ($title === '') continue;
            $productOptions[$rowId][$title] = (int)$opt['is_visible'];
        }

        $data = [];
        // Group variations by product
        $productVariations = [];
        foreach ($variations as $var) {
            $productVariations[$var['row_id']][] = $var;
        }

        foreach ($productVariations as $rowId => $vars) {
            if ($isRefillLogic) {
                // Refill Logic: ANY valid(visible) variation has <= 0 stock -> Need Refill = 1
                $needRefill = 0;

                foreach ($vars as $var) {
                    $comb = (string)$var['comb'];
                    $stock = (int)$var['stock'];
                    $parts = explode('_', $comb);

                    // Check if variation is valid (all parts visible)
                    $isValidVariation = true;
                    if (isset($productOptions[$rowId])) {
                        if (isset($productOptions[$rowId][$comb])) {
                            if ($productOptions[$rowId][$comb] === 0) {
                                $isValidVariation = false;
                            }
                        } else {
                            foreach ($parts as $part) {
                                $part = trim($part);
                                if (isset($productOptions[$rowId][$part]) && $productOptions[$rowId][$part] === 0) {
                                    $isValidVariation = false;
                                    break;
                                }
                            }
                        }
                    }

                    if ($isValidVariation) {
                        if ($stock <= 0) {
                            $needRefill = 1;
                            break; // Found one valid variation out of stock
                        }
                    }
                }

                $data[] = [
                    'store_id' => 0,
                    'row_id' => $rowId,
                    'attribute_id' => $attribute->getId(),
                    'value' => $needRefill
                ];

            } else {
                // Instock Logic: ANY valid(partially visible?) variation has > 0 stock -> InStock = 1
                $inStock = 0;
                foreach ($vars as $var) {
                    $comb = (string)$var['comb'];
                    $stock = (int)$var['stock'];
                    if ($stock <= 0) continue;

                    $parts = explode('_', $comb);
                    $hasVisiblePart = false;

                    if (isset($productOptions[$rowId])) {
                        if (isset($productOptions[$rowId][$comb]) && $productOptions[$rowId][$comb] === 1) {
                            $hasVisiblePart = true;
                        } else {
                            foreach ($parts as $part) {
                                $part = trim($part);
                                if (isset($productOptions[$rowId][$part]) && $productOptions[$rowId][$part] === 1) {
                                    $hasVisiblePart = true;
                                    break;
                                }
                            }
                        }
                    } else {
                         // No option mapping found, assume visible if variation exists
                         // Logic from original query implied matching title was required,
                         // but avoiding explicit block allows handling data edge cases.
                         $hasVisiblePart = false;
                    }

                    if ($hasVisiblePart) {
                        $inStock = 1;
                        break;
                    }
                }

                $data[] = [
                    'store_id' => 0,
                    'row_id' => $rowId,
                    'attribute_id' => $attribute->getId(),
                    'value' => $inStock
                ];
            }
        }

        if (!empty($data)) {
            $connection->insertOnDuplicate($tableName, $data);
        }
    }

    /**
     * @param array $ids
     * @return bool
     */
    public function syncLivesearchCategoriesIds(array $ids): bool
    {
        if (empty($ids)) return true;

        $ids = $this->resolveEntityIds($ids);
        if (empty($ids)) return true;

        $chunks = array_chunk($ids, self::BATCH_SIZE);
        foreach ($chunks as $chunk) {
            $this->processLivesearchCategoriesChunk($chunk);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function syncLivesearchCategoriesAll(): bool
    {
        foreach ($this->getProductIdBatch() as $batchIds) {
            $this->processLivesearchCategoriesChunk($batchIds);
        }
        return true;
    }

    public function processLivesearchCategoriesChunk(array $ids): void
    {
        try {
            $attribute = $this->getAttribute(self::LIVESEARCH_CATEGORIES);
            $mainCategoryAttribute = $this->getAttribute(self::MAIN_CATEGORY);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['cpe' => $this->getProductEntityTable()],
                ['product_row_id' => 'row_id']
            )
            ->join(
                ['cpei' => $connection->getTableName('catalog_product_entity_int')],
                'cpei.row_id = cpe.row_id AND cpei.attribute_id = :attribute_id',
                ['main_category_id' => 'value']
            )
            ->where('cpe.entity_id IN (?)', $ids);

        $bind = ['attribute_id' => $mainCategoryAttribute->getId()];
        $productsMainCategory = $connection->fetchAll($select, $bind);

        $data = [];
        foreach ($productsMainCategory as $productMainCategory) {
            $catId = $productMainCategory['main_category_id'];

            if (!isset($this->categoriesCache[$catId])) {
                $this->categoriesCache[$catId] = $this->productHelper->getCategoryHierarchy($catId);
            }
            $categoryNames = $this->categoriesCache[$catId];

            $data[] = [
                'store_id' => 0,
                'row_id' => $productMainCategory['product_row_id'],
                'attribute_id' => $attribute->getId(),
                'value' => implode(',', $categoryNames)
            ];

        }

        if (!empty($data)) {
            $connection->insertOnDuplicate($connection->getTableName('catalog_product_entity_varchar'), $data);
        }
    }

    /**
     * @param $message
     * @return void
     */
    private function log($message)
    {
        $this->logger->info($message);
    }
    /**
     * @param array $ids
     * @return bool
     */
    public function syncShopNameIds(array $ids): bool
    {
        if (empty($ids)) return true;

        $ids = $this->resolveEntityIds($ids);
        if (empty($ids)) return true;

        foreach (array_chunk($ids, self::BATCH_SIZE) as $chunk) {
            $this->processShopNameChunk($chunk);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function syncShopNameAll(): bool
    {
        foreach ($this->getProductIdBatch() as $batchIds) {
            $this->processShopNameChunk($batchIds);
        }
        return true;
    }

    private function processShopNameChunk(array $ids): void
    {
        try {
            $attribute = $this->getAttribute(self::SELLER_SHOP_NAME);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(['cpe' => $this->getProductEntityTable()], ['row_id'])
            ->join(
                ['mp' => $connection->getTableName('marketplace_product')],
                'cpe.entity_id = mp.mageproduct_id',
                []
            )
            ->join(
                ['mu' => $connection->getTableName('marketplace_userdata')],
                'mu.seller_id = mp.seller_id',
                ['seller_shop_name' => 'shop_title']
            )
            ->where('cpe.entity_id IN (?)', $ids)
            ->where('mu.shop_title IS NOT NULL AND mu.shop_title != ""');

        $data = [];
        $cursor = $connection->query($select);
        while ($item = $cursor->fetch()) {
            $data[] = [
                'store_id' => 0,
                'row_id' => $item['row_id'],
                'attribute_id' => $attribute->getId(),
                'value' => $item['seller_shop_name']
            ];
        }

        if (!empty($data)) {
            $connection->insertOnDuplicate($connection->getTableName('catalog_product_entity_varchar'), $data);
        }
    }

    /**
     * @param array $ids
     * @return bool
     */
    public function syncIds(array $ids): bool
    {
        if (empty($ids)) return true;

        $ids = $this->resolveEntityIds($ids);
        if (empty($ids)) return true;

        foreach (array_chunk($ids, self::BATCH_SIZE) as $chunk) {
            $this->processSyncByIdsChunk($chunk);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function syncAll(): bool
    {
        foreach ($this->getProductIdBatch() as $batchIds) {
            $this->processSyncByIdsChunk($batchIds);
        }
        return true;
    }

    private function processSyncByIdsChunk(array $ids): void
    {
        try {
            $attribute = $this->getAttribute(self::INDEX_SELLER_ATTRIBUTE);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(['cpe' => $this->getProductEntityTable()], ['row_id'])
            ->join(
                ['mp' => $connection->getTableName('marketplace_product')],
                'cpe.entity_id = mp.mageproduct_id',
                ['index_seller_id' => 'seller_id']
            )
            ->where('cpe.entity_id IN (?)', $ids);

        $data = [];
        $cursor = $connection->query($select);
        while ($item = $cursor->fetch()) {
            $data[] = [
                'store_id' => 0,
                'row_id' => $item['row_id'],
                'attribute_id' => $attribute->getId(),
                'value' => $item['index_seller_id']
            ];
        }

        if (!empty($data)) {
            $connection->insertOnDuplicate($connection->getTableName('catalog_product_entity_int'), $data);
        }
    }

    /**
     * @param array $ids
     * @return bool
     */
    public function syncNeedToRefillIds(array $ids): bool
    {
        if (empty($ids)) return true;

        $ids = $this->resolveEntityIds($ids);
        if (empty($ids)) return true;

        foreach (array_chunk($ids, self::BATCH_SIZE) as $chunk) {
            $this->processNeedToRefillChunk($chunk);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function syncNeedToRefillAll(): bool
    {
        foreach ($this->getProductIdBatch() as $batchIds) {
            $this->processNeedToRefillChunk($batchIds);
        }
        return true;
    }

    private function processNeedToRefillChunk(array $ids): void
    {
        try {
            $attribute = $this->getAttribute(self::NEED_TO_REFILL);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('catalog_product_entity_int');

        // 1. Check Simple Stock / Reservation
        $select = $connection->select()
            ->from(['p' => $this->getProductEntityTable()], ['row_id'])
            ->joinLeft(
                ['stock' => $connection->getTableName('cataloginventory_stock_item')],
                'p.entity_id = stock.product_id',
                ['qty']
            )
            ->joinLeft(
                ['reservation' => $connection->getTableName('inventory_reservation')],
                'reservation.sku = p.sku',
                ['reserved_qty' => new \Zend_Db_Expr('SUM(reservation.quantity)')]
            )
            ->columns(['need_to_refill' => new \Zend_Db_Expr(
                '(IFNULL(stock.qty, 0) + IFNULL(SUM(reservation.quantity), 0) <= 0)'
            )])
            ->where('p.entity_id IN (?)', $ids)
            ->group('p.row_id');

        $data = [];
        $cursor = $connection->query($select);
        while ($item = $cursor->fetch()) {
            $needToRefill = ((int)$item['need_to_refill']) ? 1 : 0;
            $data[] = [
                'store_id' => 0,
                'row_id' => $item['row_id'],
                'attribute_id' => $attribute->getId(),
                'value' => $needToRefill
            ];
        }

        if (!empty($data)) {
            $connection->insertOnDuplicate($table, $data);
        }

        // 2. Check Variations (Refill Logic)
        $this->processVariationsStockLogic($ids, $attribute, $table, true);
    }

    /**
     * @param array $ids
     * @return bool
     */
    public function syncIndexStockStatusIds(array $ids): bool
    {
        if (empty($ids)) return true;

        $ids = $this->resolveEntityIds($ids);
        if (empty($ids)) return true;

        foreach (array_chunk($ids, self::BATCH_SIZE) as $chunk) {
            $this->processIndexStockStatusChunk($chunk);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function syncIndexStockStatusAll(): bool
    {
        foreach ($this->getProductIdBatch() as $batchIds) {
            $this->processIndexStockStatusChunk($batchIds);
        }
        return true;
    }

    private function processIndexStockStatusChunk(array $ids): void
    {
        try {
            $attribute = $this->getAttribute(self::INDEX_STOCK_STATUS);
            $attribute->setStoreId(0);
            $options = $attribute->getSource()->getAllOptions();
            $inStockValue = null;
            $outStockValue = null;
            $preorderValue = null;
            foreach ($options as $option) {
                if ($option['label'] === 'In Stock') {
                    $inStockValue = $option['value'];
                } elseif ($option['label'] === 'Out of Stock') {
                    $outStockValue = $option['value'];
                } elseif ($option['label'] === 'Preorder') {
                    $preorderValue = $option['value'];
                }
            }
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            return;
        }

        $connection = $this->resourceConnection->getConnection();

        // 1. Determine Initial "In Stock" Status (Standard inventory + Variations)
        // Default to Out of Stock
        $stockStatusMap = array_fill_keys($ids, $outStockValue);

        // Check Simple Stock (cataloginventory_stock_item)
        $selectStock = $connection->select()
            ->from(['p' => $this->getProductEntityTable()], ['entity_id', 'row_id'])
            ->join(
                ['stock' => $connection->getTableName('cataloginventory_stock_item')],
                'p.entity_id = stock.product_id',
                ['is_in_stock', 'qty']
            )
            ->joinLeft(
                ['reservation' => $connection->getTableName('inventory_reservation')],
                'p.sku = reservation.sku',
                ['reserved_qty' => new \Zend_Db_Expr('SUM(reservation.quantity)')]
            )
            ->where('p.entity_id IN (?)', $ids)
            ->group('p.entity_id');

        $simpleStock = $connection->fetchAll($selectStock);
        foreach ($simpleStock as $item) {
            $salableQty = (float)$item['qty'] + (float)$item['reserved_qty'];
            if ($item['is_in_stock'] && $salableQty > 0) {
                $stockStatusMap[$item['entity_id']] = $inStockValue;
            }
        }

        // Check Variations (wk_osi_variations)
        // If product has variations, check if ANY valid variation has stock > 0
        // Logic adapted from processVariationsStockLogic but simpler (just need boolean)

        // Fetch Variations
        $selectVars = $connection->select()
            ->from(['var' => $connection->getTableName('wk_osi_variations')], ['product_id', 'comb', 'stock'])
            ->join(
                ['p' => $this->getProductEntityTable()],
                'p.row_id = var.product_id',
                ['entity_id', 'row_id']
            )
            ->where('p.entity_id IN (?)', $ids);

        $variations = $connection->fetchAll($selectVars);

        if (!empty($variations)) {
            // Need Option Visibility to validate variations
            $selectOptions = $connection->select()
                ->from(['p' => $this->getProductEntityTable()], ['row_id'])
                ->join(['op' => $connection->getTableName('catalog_product_option')], 'p.row_id = op.product_id', [])
                ->join(['opval' => $connection->getTableName('catalog_product_option_type_value')], 'op.option_id = opval.option_id', ['is_visible'])
                ->join(['optit' => $connection->getTableName('catalog_product_option_type_title')], 'opval.option_type_id = optit.option_type_id', ['title'])
                ->where('optit.store_id = 0')
                ->where('p.entity_id IN (?)', $ids);

            $optionsData = $connection->fetchAll($selectOptions);
            $productOptions = [];
            foreach ($optionsData as $opt) {
                $rowId = $opt['row_id'];
                $title = trim((string)$opt['title']);
                if ($title === '') continue;
                $productOptions[$rowId][$title] = (int)$opt['is_visible'];
            }

            $productVariations = [];
            foreach ($variations as $var) {
                $productVariations[$var['entity_id']][] = $var;
            }

            foreach ($productVariations as $entityId => $vars) {
                // If it's a variation product, we reset logic: must be determined by variations
                $hasStock = false;
                $rowId = $vars[0]['row_id']; // All vars have same row_id for this product

                foreach ($vars as $var) {
                    $comb = (string)$var['comb'];
                    $stock = (int)$var['stock'];
                    if ($stock <= 0) continue;

                    $parts = explode('_', $comb);
                    $hasVisiblePart = false;

                    if (isset($productOptions[$rowId])) {
                        if (isset($productOptions[$rowId][$comb]) && $productOptions[$rowId][$comb] === 1) {
                            $hasVisiblePart = true;
                        } else {
                            foreach ($parts as $part) {
                                $part = trim($part);
                                if (isset($productOptions[$rowId][$part]) && $productOptions[$rowId][$part] === 1) {
                                    $hasVisiblePart = true;
                                    break;
                                }
                            }
                        }
                    } else {
                         // No options mapped but variations exist? Link logic implies invisible or simple match.
                         // Assume valid if no options restrict it?
                         // Consistent with processVariationsStockLogic
                         $hasVisiblePart = false;
                    }

                    if ($hasVisiblePart) {
                        $hasStock = true;
                        break;
                    }
                }

                $stockStatusMap[$entityId] = $hasStock ? $inStockValue : $outStockValue;
            }
        }

        // 2. Process Preorder for Out of Stock items
        // Filter IDs that are currently determined as OOS
        $oosIds = [];
        foreach ($stockStatusMap as $entityId => $status) {
            if ($status == $outStockValue) {
                $oosIds[] = $entityId;
            }
        }

        if (!empty($oosIds)) {
            // Load Collection for OOS items to check Preorder attributes & logic
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addFieldToFilter('entity_id', ['in' => $oosIds]);

            $preorderAttribute = $this->config->getAttribute(Product::ENTITY, 'wk_marketplace_preorder');
            $preorderEnabledOptionId = $preorderAttribute ? $preorderAttribute->getSource()->getOptionId('Enable') : null;

            foreach ($collection as $product) {
                $productId = $product->getId();
                // Skip if variation product (Logic: Variation products handled above are either In Stock or OOS)
                // However, if variations list didn't include this ID (not in wk_osi_variations), it's a simple product check
                // If it WAS in wk_osi_variations, stockStatusMap was forced to InStock or OOS.
                // Preorder only applies if NOT a variation product (per StockStatusDataProvider logic: if ($arguments['isVariation']...))
                // How to know if it's a variation product here?
                // We can check if it was in $productVariations keys.
                if (isset($productVariations[$productId])) {
                    continue;
                }

                $isPreorder = false;

                // Preorder Logic
                $sellerId = $this->marketplacePreorderHelper->getSellerIdByProductId($productId);
                $preorderAction = $this->marketplacePreorderHelper->getSellerPreorderAction($sellerId);

                // Perform Seller Action Check
                $status = 0;
                if ((int)$preorderAction == 1) {
                    $status = 1;
                } elseif ((int)$preorderAction == 2) {
                    $filterProduct = $this->marketplacePreorderHelper->getFilterProducts(2, $sellerId); // This might be heavy
                    $filterProductArray = $this->marketplacePreorderHelper->getProductIdsFromSku($filterProduct, $sellerId);
                    if (in_array($productId, $filterProductArray)) {
                        $status = 1;
                    }
                } elseif ((int)$preorderAction == 3) {
                    $filterProduct = $this->marketplacePreorderHelper->getFilterProducts(3, $sellerId);
                    $filterProductArray = $this->marketplacePreorderHelper->getProductIdsFromSku($filterProduct, $sellerId);
                    if (!in_array($productId, $filterProductArray)) {
                        $status = 1;
                    }
                } else {
                    if ($preorderEnabledOptionId && $product->getWkMarketplacePreorder() == $preorderEnabledOptionId) {
                        $status = 1;
                    }
                }

                if ($status == 1) {
                    $todayDate = $this->timezone->date()->format('Y-m-d 00:00:00');
                    $todayTime = strtotime($todayDate);
                    $preorderMode = $product->getData('preorder_mode');

                    if ($preorderMode == \Branch8\Preorder\Model\Source\PreorderMode::START_END_DATE) {
                        $preorderStartDate = (string)$product->getData('preorder_start_date');
                        $preorderStartDate = substr($preorderStartDate, 0, 10).' 00:00:00';
                        $preorderEndDate = (string)$product->getData('preorder_end_date');
                        $preorderEndDate = substr($preorderEndDate, 0, 10).' 23:59:59';

                        if ($preorderStartDate && (string)$preorderStartDate != '' && $todayTime >= strtotime($preorderStartDate)
                            && $preorderEndDate && (string)$preorderEndDate != '' && $todayTime <= strtotime($preorderEndDate)) {
                            if ((int)$product->getData('preorder_use_qty') == 0) {
                                $isPreorder = true;
                            } else {
                                if ($product->getData('wk_mppreorder_qty') > 0) {
                                    $isPreorder = true;
                                }
                            }
                        }
                    } elseif ($preorderMode == \Branch8\Preorder\Model\Source\PreorderMode::X_DAYS) {
                        $preorderEndDate = $product->getData('preorder_end_date');
                        if ($preorderEndDate && (string)$preorderEndDate != '' && $todayTime <= strtotime($preorderEndDate)) {
                            $isPreorder = true;
                        }
                    } elseif ($preorderMode == \Branch8\Preorder\Model\Source\PreorderMode::SPECIFY_SHIPPING_DATE) {
                         $preorderEndDate = $product->getData('preorder_end_date');
                         if ($preorderEndDate && (string)$preorderEndDate != '' && $todayTime <= strtotime($preorderEndDate)) {
                             $isPreorder = true;
                         }
                    }
                }

                if ($isPreorder) {
                    $stockStatusMap[$productId] = $preorderValue;
                }
            }
        }

        // Prepare Data for Insert
        $data = [];
        $idsToRowId = [];
        // We need row_id for insertion.
        // We already fetched some row_ids. Let's fetch map for all if missing.
        $selectRowIds = $connection->select()
            ->from($this->getProductEntityTable(), ['entity_id', 'row_id'])
            ->where('entity_id IN (?)', $ids);
        $idsToRowId = $connection->fetchPairs($selectRowIds);

        foreach ($stockStatusMap as $entityId => $statusValue) {
            if (isset($idsToRowId[$entityId])) {
                $data[] = [
                    'store_id' => 0,
                    'row_id' => $idsToRowId[$entityId],
                    'attribute_id' => $attribute->getId(),
                    'value' => $statusValue
                ];
            }
        }

        if (!empty($data)) {
            $connection->insertOnDuplicate($connection->getTableName('catalog_product_entity_int'), $data);
        }
    }

    /**
     * Get all product IDs in batches
     * @return \Generator
     */
    private function getProductIdBatch(): \Generator
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->getProductEntityTable();
        $minId = 0;

        while (true) {
            $select = $connection->select()
                ->from($table, 'entity_id')
                ->where('entity_id > ?', $minId)
                ->order('entity_id ASC')
                ->limit(self::BATCH_SIZE);

            $ids = $connection->fetchCol($select);

            if (empty($ids)) {
                break;
            }

            $minId = end($ids);
            yield $ids;
        }
    }
}

