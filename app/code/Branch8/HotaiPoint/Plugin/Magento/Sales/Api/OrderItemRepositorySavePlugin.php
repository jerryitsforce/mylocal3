<?php

declare(strict_types=1);

namespace Branch8\HotaiPoint\Plugin\Magento\Sales\Api;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiPoint\Helper\Common as HotaiPointCommonHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;

class OrderItemRepositorySavePlugin
{
    private const LOG_FOLDER_NAME = 'HotaiPoint/Plugin/OrderItemRepositorySave';

    public function __construct(
        private HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        private HotaiPointCommonHelper $hotaiPointCommonHelper,
        private ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @param OrderItemRepositoryInterface $subject
     * @param callable $proceed
     * @param OrderItemInterface $entity
     * @param mixed $saveOptions
     * @return OrderItemInterface
     */
    public function aroundSave(
        OrderItemRepositoryInterface $subject,
        callable $proceed,
        OrderItemInterface $entity,
        $saveOptions = null
    ) {
        try {
            $isTrackingEnabled = (bool) $this->hotaiPointCommonHelper->getHotaiPointConfig(
                HotaiPointCommonHelper::HOTAI_POINT_CONFIG_PATH_RETURN_POINT_FIELDS_CLEAN_TRACKING
            );
        } catch (\Throwable $e) {
            $this->writeLogSafe([
                'Title' => 'OrderItemRepositorySavePlugin::aroundSave - get config failed, skip tracking.',
                'Exception' => $e->getMessage(),
            ]);
            return $proceed($entity, $saveOptions);
        }

        if (!$isTrackingEnabled) {
            return $proceed($entity, $saveOptions);
        }

        try {
            $itemId = (int) $entity->getItemId();
        } catch (\Throwable $e) {
            $this->writeLogSafe([
                'Title' => 'OrderItemRepositorySavePlugin::aroundSave - getItemId failed, skip tracking.',
                'Exception' => $e->getMessage(),
            ]);
            return $proceed($entity, $saveOptions);
        }

        try {
            $beforeData = $this->getItemHotaiReturnPointData($itemId);
        } catch (\Throwable $e) {
            $this->writeLogSafe([
                'Title' => 'OrderItemRepositorySavePlugin::aroundSave - load before entity failed (SQL).',
                'Item ID' => $itemId,
                'Exception' => $e->getMessage(),
            ]);
            return $proceed($entity, $saveOptions);
        }

        /** @var OrderItemInterface $result */
        $result = $proceed($entity, $saveOptions);

        if ($itemId) {
            try {
                $afterData = $this->getItemHotaiReturnPointData($itemId, true);

                if (!empty($beforeData)) {
                    $this->logIfHotaiReturnPointCleared($itemId, $beforeData, $afterData);
                }
            } catch (\Throwable $e) {
                $this->writeLogSafe([
                    'Title' => 'OrderItemRepositorySavePlugin::aroundSave - load after entity failed (SQL).',
                    'Item ID' => $itemId,
                    'Exception' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * @param int $itemId
     * @param bool $withOrderId
     * @return array|null
     */
    private function getItemHotaiReturnPointData(int $itemId, bool $withOrderId = false): ?array
    {
        if ($itemId <= 0) {
            return null;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('sales_order_item');

        $columns = [
            'hotai_point_return_point_trace_no',
            'hotai_point_return_point_trans_datetime',
        ];

        if ($withOrderId) {
            $columns[] = 'order_id';
        }

        $select = $connection->select()
            ->from(
                $tableName,
                $columns
            )
            ->where('item_id = ?', $itemId);

        return $connection->fetchRow($select) ?: null;
    }

    /**
     * @param int $itemId
     * @param array|null $beforeData
     * @param array|null $afterData
     * @return void
     */
    private function logIfHotaiReturnPointCleared(int $itemId, ?array $beforeData, ?array $afterData): void
    {
        if (empty($beforeData) || empty($afterData)) {
            return;
        }

        $changedFromHasValueToEmpty = [];

        foreach ($beforeData as $field => $beforeValue) {
            $afterValue = $afterData[$field] ?? null;

            $beforeHasValue = !($beforeValue === null || $beforeValue === '');
            $afterIsEmpty = ($afterValue === null || $afterValue === '');

            if ($beforeHasValue && $afterIsEmpty) {
                $changedFromHasValueToEmpty[$field] = [
                    'before' => $beforeValue,
                    'after' => $afterValue,
                ];
            }
        }

        if (!empty($changedFromHasValueToEmpty)) {
            $this->writeLogSafe(
                json_encode([
                    'Title' => 'Order item HotaiPoint return fields changed from value to empty after save (SQL).',
                    'Item ID' => $itemId,
                    'Order ID' => $afterData['order_id'] ?? null,
                    'Changed fields' => $changedFromHasValueToEmpty,
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
                ])
            );
        }
    }

    /**
     * 寫入 log，對 writeLog 做 try-catch 靜默處理，避免寫 log 失敗影響主流程。
     *
     * @param array|string $data
     * @param string $folder
     * @return void
     */
    private function writeLogSafe(array|string $data, string $folder = self::LOG_FOLDER_NAME): void
    {
        try {
            $this->hotaiCoreCommonHelper->writeLog($data, $folder);
        } catch (\Throwable $e) {
            // 靜默處理，不影響主流程
        }
    }
}

