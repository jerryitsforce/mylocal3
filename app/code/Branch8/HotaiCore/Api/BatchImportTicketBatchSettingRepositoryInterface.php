<?php

namespace Branch8\HotaiCore\Api;

use Branch8\HotaiCore\Api\Data\BatchImportTicketBatchSettingModelInterface;

/**
 * 匯入類票券專用 BatchSetting Repository 介面
 * 約束僅提供：依 batchCode + productId 取得、建立新實例、儲存；不處理 request 解析與寫入
 */
interface BatchImportTicketBatchSettingRepositoryInterface
{
    /**
     * 依貨號與商品 ID 取得既有 BatchSetting
     *
     * @param string $batchCode
     * @param int    $productId
     * @return BatchImportTicketBatchSettingModelInterface|null
     */
    public function getBatchSettingByBatchCodeAndProductId(
        string $batchCode,
        int $productId
    ): ?BatchImportTicketBatchSettingModelInterface;

    /**
     * 建立新的 BatchSetting 實例（未儲存）
     *
     * @return BatchImportTicketBatchSettingModelInterface
     */
    public function createNew(): BatchImportTicketBatchSettingModelInterface;

    /**
     * 儲存 BatchSetting
     *
     * @param BatchImportTicketBatchSettingModelInterface $model
     * @return void
     */
    public function save(BatchImportTicketBatchSettingModelInterface $model): void;
}
