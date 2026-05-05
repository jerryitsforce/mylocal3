<?php

namespace Branch8\HotaiCore\Helper;

use Branch8\HotaiCore\Api\BatchImportTicketBatchSettingRepositoryInterface;
use Branch8\HotaiCore\Api\Data\BatchImportTicketBatchSettingModelInterface;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Branch8\MarketplaceStaging\Helper\Data as MarketplaceStagingHelper;
use Magento\Customer\Model\CustomerFactory;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;

class TicketImport
{
    public const ALPHA_POOL = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    public const NUM_POOL   = '0123456789';
    public const MAX_COUNT  = 100000;

    public const IMPORT_MODE_MANUAL = 'manual';
    public const IMPORT_MODE_AUTO   = 'auto';

    /** 通知期限類型：依售完/使用截止日 */
    public const NOTIFY_LIMIT_TYPE_SALE_END_TIME = 1;
    /** 通知期限類型：依有效天數 */
    public const NOTIFY_LIMIT_TYPE_DUE_DAYS = 2;

    protected ResourceConnection $resourceConnection;
    protected VirtualProductHelper $virtualProductHelper;
    protected ProductRepository $productRepository;
    protected SourceItemInterfaceFactory $sourceItemFactory;
    protected SourceItemsSaveInterface $sourceItemsSaveInterface;
    protected StockRegistryInterface $stockRegistry;
    protected AuthSession $authSession;
    protected MarketplaceStagingHelper $marketplaceStagingHelper;
    protected CustomerFactory $customerFactory;
    protected ProductAction $productAction;
    protected MarketplaceHelper $marketplaceHelper;

    public function __construct(
        ResourceConnection $resourceConnection,
        VirtualProductHelper $virtualProductHelper,
        ProductRepository $productRepository,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSaveInterface,
        StockRegistryInterface $stockRegistry,
        AuthSession $authSession,
        MarketplaceStagingHelper $marketplaceStagingHelper,
        CustomerFactory $customerFactory,
        ProductAction $productAction,
        MarketplaceHelper $marketplaceHelper
    ) {
        $this->resourceConnection     = $resourceConnection;
        $this->virtualProductHelper   = $virtualProductHelper;
        $this->productRepository      = $productRepository;
        $this->sourceItemFactory      = $sourceItemFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
        $this->stockRegistry          = $stockRegistry;
        $this->authSession            = $authSession;
        $this->marketplaceStagingHelper = $marketplaceStagingHelper;
        $this->customerFactory        = $customerFactory;
        $this->productAction          = $productAction;
        $this->marketplaceHelper      = $marketplaceHelper;
    }

    /**
     * 自動生成票券序號
     *
     * @param string $prefix   固定開頭，可為空字串
     * @param int    $length   序號長度（不含 prefix），需 > 0
     * @param string $charType 字元組成類型：alnum / num / alpha
     * @param int    $count    生成組數，需 > 0，最大 100000
     *
     * @return array{
     *     is_success: bool,
     *     generated_count: int,
     *     duplicate_count: int,
     *     serial_numbers: string[],
     *     exception_message: string|null
     * }
     */
    public function generateSerialNumbers(
        string $prefix,
        int $length,
        string $charType,
        int $count
    ): array {
        $result = [
            'is_success'        => false,
            'generated_count'   => 0,
            'duplicate_count'   => 0,
            'serial_numbers'    => [],
            'exception_message' => null,
        ];

        try {
            if ($length <= 0) {
                throw new \InvalidArgumentException('Serial length must be greater than 0.');
            }

            if ($count > self::MAX_COUNT) {
                $count = self::MAX_COUNT;
            }

            switch ($charType) {
                case 'num':
                    $pool = self::NUM_POOL;
                    break;
                case 'alpha':
                    $pool = self::ALPHA_POOL;
                    break;
                case 'alnum':
                default:
                    $pool = self::ALPHA_POOL . self::NUM_POOL;
                    break;
            }

            $poolLength = strlen($pool);

            if ($poolLength === 0) {
                throw new \InvalidArgumentException('Character pool is empty.');
            }

            $serials = [];

            for ($i = 0; $i < $count; $i++) {
                $body = '';

                for ($j = 0; $j < $length; $j++) {
                    $index = random_int(0, $poolLength - 1);
                    $body .= $pool[$index];
                }

                $serials[] = $prefix . $body;
            }

            $uniqueSerials = array_values(array_unique($serials));

            $result['is_success']      = true;
            $result['generated_count'] = count($serials);
            $result['duplicate_count'] = count($serials) - count($uniqueSerials);
            $result['serial_numbers']  = $uniqueSerials;
        } catch (\Throwable $e) {
            $result['exception_message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * 將票券序號資料匯入指定資料表（INSERT IGNORE）
     *
     * @param string $tableName
     * @param int    $productId
     * @param int    $batchSettingId
     * @param array  $ticketDataArray 每筆為一個票券序號字串，例如 ['abc', 'def']
     *
     * @return array{
     *     is_success: bool,
     *     import_count: int,
     *     inserted_count: int,
     *     exception_message: string|null
     * }
     */
    public function import(
        string $tableName,
        int $productId,
        int $batchSettingId,
        array $ticketDataArray,
        int $sellerId
    ): array {
        $result = [
            'is_success'        => false,
            'import_count'      => count($ticketDataArray),
            'inserted_count'    => 0,
            'exception_message' => null,
        ];

        if (empty($ticketDataArray)) {
            $result['is_success'] = true;

            return $result;
        }

        if ($sellerId <= 0) {
            throw new \InvalidArgumentException('Seller ID is required for ticket import.');
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName  = $this->resourceConnection->getTableName($tableName);

            // 將傳入的序號字串轉成實際要寫入資料表的欄位資料
            $rows = [];
            foreach ($ticketDataArray as $serial) {
                if (!is_string($serial) || $serial === '') {
                    continue;
                }

                $row = [
                    'batch_setting_id'     => $batchSettingId,
                    'belong_to_product_id' => $productId,
                    'serial_number'        => $serial,
                    'seller_id'            => $sellerId,
                ];

                $rows[] = $row;
            }

            if (empty($rows)) {
                $result['is_success'] = true;

                return $result;
            }

            // 以第一筆資料決定欄位集合
            $firstRow = reset($rows);
            $columns  = array_keys($firstRow);
            $quotedColumns  = array_map([$connection, 'quoteIdentifier'], $columns);
            $rowPlaceholder = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';

            $valuePlaceholders = [];
            $bindValues        = [];

            foreach ($rows as $row) {
                $valuePlaceholders[] = $rowPlaceholder;

                foreach ($columns as $column) {
                    $bindValues[] = $row[$column] ?? null;
                }
            }

            if (empty($valuePlaceholders)) {
                $result['is_success'] = true;

                return $result;
            }

            $sql = sprintf(
                'INSERT IGNORE INTO %s (%s) VALUES %s',
                $connection->quoteIdentifier($tableName),
                implode(',', $quotedColumns),
                implode(',', $valuePlaceholders)
            );

            $statement = $connection->query($sql, $bindValues);

            $result['is_success']     = true;
            $result['inserted_count'] = $statement->rowCount();
        } catch (\Throwable $e) {
            $result['exception_message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * 依參數自動生成票券序號並匯入指定資料表
     *
     * @param string $prefix
     * @param int    $length       生成序號的總長度（含 prefix）
     * @param string $charType
     * @param int    $groupCount
     * @param int    $productId
     * @param int    $batchSettingId
     * @param int    $sellerId
     * @param string $tableName
     *
     * @return array{
     *     requested_count: int,
     *     duplicate_count: int,
     *     inserted_count: int
     * }
     */
    public function handleAutoImport(
        string $prefix,
        int $length,
        string $charType,
        int $groupCount,
        int $productId,
        int $batchSettingId,
        int $sellerId,
        string $tableName
    ): array {
        if (empty($groupCount)) {
            return [
                'requested_count' => 0,
                'duplicate_count' => 0,
                'inserted_count'  => 0,
            ];
        }

        $prefixLength = strlen($prefix);
        if ($length <= $prefixLength) {
            throw new \InvalidArgumentException('Serial total length must be greater than prefix length.');
        }

        $bodyLength = $length - $prefixLength;

        $generateResult = $this->generateSerialNumbers(
            $prefix,
            $bodyLength,
            $charType,
            $groupCount
        );

        if (!$generateResult['is_success']) {
            $message = $generateResult['exception_message'] ?? 'Failed to generate ticket serial numbers.';
            throw new \RuntimeException($message);
        }

        $serialNumbers = $generateResult['serial_numbers'] ?? [];

        if (empty($serialNumbers)) {
            throw new \RuntimeException('No ticket serial numbers generated.');
        }

        $importResult = $this->import($tableName, $productId, $batchSettingId, $serialNumbers, $sellerId);

        if (!$importResult['is_success']) {
            $message = $importResult['exception_message'] ?? 'Failed to import ticket records.';
            throw new \RuntimeException($message);
        }

        return [
            'requested_count' => $groupCount,
            'duplicate_count' => (int) ($generateResult['duplicate_count'] ?? 0),
            'inserted_count'  => (int) ($importResult['inserted_count'] ?? 0),
        ];
    }

    /**
     * 依 request 參數與台灣時區整理後，寫入 BatchSetting 的 setter（共用）
     *
     * @param BatchImportTicketBatchSettingModelInterface $model
     * @param RequestInterface                             $request
     * @param int                                          $sellerId
     * @param int                                          $productId
     * @return void
     */
    public function populateBatchSettingFromRequest(
        BatchImportTicketBatchSettingModelInterface $model,
        RequestInterface $request,
        int $sellerId,
        int $productId
    ): void {
        $model->setSellerId($sellerId);
        $model->setBelongToProductId($productId);

        if ($request->getParam('batch_code') !== null && $request->getParam('batch_code') !== '') {
            $model->setBatchCode((string) $request->getParam('batch_code'));
        }

        if ($request->getParam('sale_start_time') !== null && $request->getParam('sale_start_time') !== '') {
            $model->setSaleStartTime($this->formatTaiwanDateStart($request->getParam('sale_start_time')));
        }

        if ($request->getParam('sale_end_time') !== null && $request->getParam('sale_end_time') !== '') {
            $model->setSaleEndTime($this->formatTaiwanDateEnd($request->getParam('sale_end_time')));
        }

        if ($request->getParam('use_start_time') !== null && $request->getParam('use_start_time') !== '') {
            $model->setUseStartTime($this->formatTaiwanDateStart($request->getParam('use_start_time')));
        }

        $notifyLimitType = (int) $request->getParam('notify_limit_type');
        if ($notifyLimitType === self::NOTIFY_LIMIT_TYPE_SALE_END_TIME
            && $request->getParam('use_end_time') !== null
            && $request->getParam('use_end_time') !== '') {
            $model->setUseEndTime($this->formatTaiwanDateEnd($request->getParam('use_end_time')));
        }
        if ($notifyLimitType === self::NOTIFY_LIMIT_TYPE_DUE_DAYS
            && $request->getParam('due_days') !== null
            && $request->getParam('due_days') !== '') {
            $model->setDueDays((int) $request->getParam('due_days'));
        }
    }

    /**
     * 解析或建立 BatchSetting，依 request 寫入後儲存（共用，呼叫 repository 的 get/createNew/save + populateBatchSettingFromRequest）
     *
     * @param BatchImportTicketBatchSettingRepositoryInterface $repository
     * @param string                                            $batchCode
     * @param int                                               $productId
     * @param RequestInterface                                  $request
     * @param int                                               $sellerId
     * @return BatchImportTicketBatchSettingModelInterface
     */
    public function resolveOrCreateAndPopulateFromRequest(
        BatchImportTicketBatchSettingRepositoryInterface $repository,
        string $batchCode,
        int $productId,
        RequestInterface $request,
        int $sellerId
    ): BatchImportTicketBatchSettingModelInterface {
        $model = $repository->getBatchSettingByBatchCodeAndProductId($batchCode, $productId);
        if ($model === null) {
            $model = $repository->createNew();
        }
        $this->populateBatchSettingFromRequest($model, $request, $sellerId, $productId);
        $repository->save($model);

        return $model;
    }

    /**
     * 取得產品當下可出售的票券數量（共用）
     *
     * @param object $recordRepository 需提供 getAvailableForSaleRecordsByProductId(int $productId)
     * @param int    $productId
     * @return int
     */
    public function getAvailableQuantity(object $recordRepository, int $productId): int
    {
        $availableForSaleCollection = $recordRepository->getAvailableForSaleRecordsByProductId($productId);
        $availableForSaleArray      = $availableForSaleCollection->getItems();

        return count($availableForSaleArray);
    }

    /**
     * 建立批次設定的商品自訂選項（共用）
     *
     * @param VirtualProductHelper $virtualProductHelper
     * @param int|string           $productId
     * @param string               $batchSettingCode
     * @param int|string           $batchSettingId
     * @return void
     */
    public function createBatchSettingCustomOption(
        int|string $productId,
        string $batchSettingCode,
        int|string $batchSettingId
    ): void {
        $this->virtualProductHelper->createBatchSettingCustomOptionValue(
            $productId,
            [
                [
                    'title' => $batchSettingCode,
                    'sku'   => $batchSettingId,
                ]
            ]
        );
    }

    /**
     * 更新產品的庫存（共用）
     *
     * @param ProductRepository        $productRepository
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface $sourceItemsSaveInterface
     * @param StockRegistryInterface   $stockRegistry
     * @param AuthSession              $authSession
     * @param int                      $productId
     * @param int                      $quantity
     * @return void
     */
    public function updateProductQuantity(
        int $productId,
        int $quantity
    ): void {
        $product = $this->productRepository->getById($productId);
        $sku     = $product->getSku();

        // Ensure admin or seller user info is preserved or updated before sync
        $updatedUser = null;
        if ($this->authSession->getUser()) {
            $updatedUser = $this->authSession->getUser()->getUserName();
        } elseif ($this->marketplaceHelper->isSeller()) {
            $customerId = $this->marketplaceHelper->getCustomerId();
            if ($customerId) {
                $customer = $this->customerFactory->create()->load($customerId);
                $updatedUser = $customer->getData('prefix') . " " . $customer->getFirstname() . ' ' . $customer->getLastname();
            }
        }

        if ($updatedUser) {
            $this->productAction->updateAttributes([$productId], ['admin_user_updated' => $updatedUser], 0);
        }

        // Dynamically invoke the correct Ticket Synchronizer using Strategy Pattern
        // This ensures Webkul variation records correctly mirror the imported serial numbers
        $this->marketplaceStagingHelper->syncTicketVariations($product);

        /** @var SourceItemInterface $sourceItem */
        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default');
        $sourceItem->setSku($sku);
        $sourceItem->setQuantity($quantity);
        $sourceItem->setStatus($quantity ? SourceItemInterface::STATUS_IN_STOCK : SourceItemInterface::STATUS_OUT_OF_STOCK);

        $this->sourceItemsSaveInterface->execute([$sourceItem]);
        $this->updateStock($sku, $quantity);
    }

    /**
     * Updates the stock quantity for a product if required（共用內部使用）
     *
     * @param StockRegistryInterface $stockRegistry
     * @param AuthSession            $authSession
     * @param string                 $sku
     * @param float                  $quantity
     * @return void
     */
    private function updateStock(
        string $sku,
        float $quantity
    ): void {
        if ($this->authSession->getUser()) {
            return;
        }
        $stockItem = $this->stockRegistry->getStockItemBySku($sku);
        $stockItem->setQty($quantity);
        $stockItem->setIsInStock((bool) $quantity);
        $this->stockRegistry->updateStockItemBySku($sku, $stockItem);
    }

    /**
     * 將日期字串轉為台灣時區當日 00:00:00
     */
    private function formatTaiwanDateStart(string $dateParam): string
    {
        $taiwanDateObj = new \DateTime();
        $timestamp     = strtotime($dateParam . ' Asia/Taipei');
        $taiwanDateObj->setTimezone(new \DateTimeZone('Asia/Taipei'));
        $taiwanDateObj->setTimestamp($timestamp);
        $taiwanDateObj->setTime(0, 0, 0);

        return $taiwanDateObj->format('Y-m-d H:i:s');
    }

    /**
     * 將日期字串轉為台灣時區當日 23:59:59
     */
    private function formatTaiwanDateEnd(string $dateParam): string
    {
        $taiwanDateObj = new \DateTime();
        $timestamp     = strtotime($dateParam . ' Asia/Taipei');
        $taiwanDateObj->setTimezone(new \DateTimeZone('Asia/Taipei'));
        $taiwanDateObj->setTimestamp($timestamp);
        $taiwanDateObj->setTime(23, 59, 59);

        return $taiwanDateObj->format('Y-m-d H:i:s');
    }

    /**
     * 從上傳的 CSV 檔案中解析出序號字串陣列（共用）
     *
     * @param RequestInterface $request
     * @param string           $fileField    上傳欄位名稱，例如 'file'
     * @param int              $dataIndex    每列中，序號所在的欄位 index（預設 0）
     * @param int              $headerLines  前幾列為標題需略過（預設 2）
     * @return string[]
     */
    public function extractSerialsFromCsvUpload(
        RequestInterface $request,
        string $fileField = 'file',
        int $dataIndex = 0,
        int $headerLines = 2
    ): array {
        $result = [];
        /** @var \Magento\Framework\App\Request\Http $request */
        $file   = $request->getFiles($fileField);

        if (empty($file['tmp_name'])) {
            return [];
        }

        $csvString = file_get_contents($file['tmp_name']);
        $csvString = $this->purifyCsvString($csvString);
        $lines     = explode(\PHP_EOL, $csvString);

        foreach ($lines as $index => $line) {
            if ($index < $headerLines) {
                continue;
            }
            $row    = str_getcsv($line, ',', '"');
            $serial = $row[$dataIndex] ?? '';
            if (is_string($serial) && $serial !== '') {
                $result[] = $serial;
            }
        }

        return $result;
    }

    /**
     * 清除 CSV 字串中可能存在的不可視字元（例如 UTF-8 BOM）
     */
    private function purifyCsvString(string $csvString): string
    {
        $purifiedString = ltrim($csvString, "\xEF\xBB\xBF");

        return rtrim($purifiedString);
    }
}
