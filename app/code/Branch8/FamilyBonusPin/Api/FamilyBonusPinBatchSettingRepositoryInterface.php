<?php

namespace Branch8\FamilyBonusPin\Api;

use Branch8\FamilyBonusPin\Api\Data\FamilyBonusPinBatchSettingInterface;
use Branch8\HotaiCore\Api\BatchImportTicketBatchSettingRepositoryInterface;
use Branch8\HotaiCore\Api\Data\BatchImportTicketBatchSettingModelInterface;

interface FamilyBonusPinBatchSettingRepositoryInterface extends BatchImportTicketBatchSettingRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function getBatchSettingByBatchCodeAndProductId(
        string $batchCode,
        int $productId
    ): ?BatchImportTicketBatchSettingModelInterface;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\FamilyBonusPin\Api\Data\FamilyBonusPinBatchSettingSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Retrieve batch setting by id.
     *
     * @param int $id
     * @return null|\Branch8\FamilyBonusPin\Api\Data\FamilyBonusPinBatchSettingInterface
     */
    public function getById(int $id);
}
