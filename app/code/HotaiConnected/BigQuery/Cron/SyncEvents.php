<?php
namespace HotaiConnected\BigQuery\Cron;

use HotaiConnected\BigQuery\Helper\Data as BigQueryHelper;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class SyncEvents
{
    protected $bigQueryHelper;
    protected $resource;
    protected $logger;

    public function __construct(
        BigQueryHelper $bigQueryHelper,
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        $this->bigQueryHelper = $bigQueryHelper;
        $this->resource = $resource;
        $this->logger = $logger;
    }

    public function execute()
    {
        $successCount = 0;
        $failCount = 0;
        $targetDate = date('Ymd', strtotime('-1 day'));

        try {
            // 1. 動態計算昨天的日期 (格式：YYYYMMDD)
            $tableName = "hotaigo-f4da4.analytics_467880377.events_{$targetDate}";

            $this->logger->info("Hotai BigQuery Cron [Date: {$targetDate}]: Starting sync for table {$tableName}");

            // 2. 執行 BigQuery 查詢
            $query = "
                SELECT
                  user_id,
                  item.item_id,
                  event_timestamp,
                  device.category,
                  platform
                FROM `{$tableName}`, unnest(items) as item
                WHERE event_name = 'view_item'
                AND user_id IS NOT NULL;
            ";

            $results = $this->bigQueryHelper->select($query);

            // 3. 準備寫入 Magento 資料庫
            $connection = $this->resource->getConnection();
            $dbTableName = $this->resource->getTableName('raw_product_view_log');

            $batchData = [];
            $batchSize = 500;

            foreach ($results as $row) {
                $batchData[] = [
                    'uid'             => $row['user_id'],
                    'product_id'      => $row['item_id'],
                    'event_timestamp' => $this->formatEventTimestamp($row['event_timestamp']),
                    'device_category' => $row['category'],
                    'platform'        => $row['platform'],
                    'created_at'      => $this->getTaiwanCurrentDatetime()
                ];

                if (count($batchData) >= $batchSize) {
                    $this->processBatch($connection, $dbTableName, $batchData, $successCount, $failCount, $targetDate);
                    $batchData = [];
                }
            }

            // 處理剩餘資料
            if (!empty($batchData)) {
                $this->processBatch($connection, $dbTableName, $batchData, $successCount, $failCount, $targetDate);
            }

            $this->logger->info("Hotai BigQuery Cron [Date: {$targetDate}]: Finished. Success: {$successCount}, Fail: {$failCount}.");

        } catch (\Exception $e) {
            $this->logger->error("Hotai BigQuery Cron [Date: {$targetDate}] Fatal Error: " . $e->getMessage());
        }
    }

    protected function getTaiwanCurrentDatetime(): string
    {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));

        return $taiwanDateObj->format("Y-m-d H:i:s");
    }

    /**
     * 將 BigQuery 的 event_timestamp (通常為微秒) 轉為台灣時區的 datetime 字串
     */
    protected function formatEventTimestamp($timestampMicros): string
    {
        if (empty($timestampMicros)) {
            return $this->getTaiwanCurrentDatetime();
        }
        
        // BigQuery GA4 event_timestamp 是微秒
        $seconds = (int)($timestampMicros / 1000000);
        
        $dateObj = new \DateTime("@$seconds");
        $dateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));

        return $dateObj->format("Y-m-d H:i:s");
    }

    /**
     * 處理批次寫入
     */
    private function processBatch($connection, $tableName, $data, &$successCount, &$failCount, $targetDate)
    {
        try {
            $connection->insertMultiple($tableName, $data);
            $successCount += count($data);
        } catch (\Exception $e) {
            $this->logger->warning("Hotai BigQuery Cron [Date: {$targetDate}]: Batch insert failed, switching to single insert mode. Error: " . $e->getMessage());
            
            // 如果整批寫入失敗，改為逐筆寫入以追蹤詳細錯誤
            foreach ($data as $item) {
                try {
                    $connection->insert($tableName, $item);
                    $successCount++;
                } catch (\Exception $singleEx) {
                    $failCount++;
                    $this->logger->error(sprintf(
                        "Hotai BigQuery Cron [Date: %s]: Insert failed for uid: %s, product_id: %s. Error: %s",
                        $targetDate,
                        $item['uid'],
                        $item['product_id'],
                        $singleEx->getMessage()
                    ));
                    // 紀錄詳細資料以供檢查
                    $this->logger->debug("Failed data detail: " . json_encode($item));
                }
            }
        }
    }
}
