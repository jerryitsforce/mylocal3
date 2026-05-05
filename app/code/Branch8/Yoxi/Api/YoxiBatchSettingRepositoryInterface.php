<?php

namespace Branch8\Yoxi\Api;

use Branch8\HotaiCore\Api\BatchImportTicketBatchSettingRepositoryInterface;
use Branch8\HotaiCore\Api\Data\BatchImportTicketBatchSettingModelInterface;

interface YoxiBatchSettingRepositoryInterface extends BatchImportTicketBatchSettingRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function getBatchSettingByBatchCodeAndProductId(
        string $batchCode,
        int $productId
    ): ?BatchImportTicketBatchSettingModelInterface;

    /**
     * @inheritdoc
     */
    public function createNew(): BatchImportTicketBatchSettingModelInterface;

    /**
     * @inheritdoc
     */
    public function save(BatchImportTicketBatchSettingModelInterface $model): void;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\Yoxi\Api\Data\YoxiBatchSettingSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Retrieve batch setting by id.
     *
     * @param int $id
     * @return null|\Branch8\Yoxi\Model\YoxiBatchSetting
     */
    public function getById(int $id);
}
