<?php

namespace Branch8\HotaiCore\Api\Data;

/**
 * 匯入類票券專用 BatchSetting Model 介面
 * 約束四種票券型別（Yoxi / GeneralNotify / GeneralNonNotify / FamilyBonusPin）的 BatchSetting 必須提供之方法
 */
interface BatchImportTicketBatchSettingModelInterface
{
    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @return string
     */
    public function getBatchCode(): string;

    /**
     * @param int $sellerId
     * @return void
     */
    public function setSellerId(int $sellerId);

    /**
     * @param int $belongToProductId
     * @return void
     */
    public function setBelongToProductId(int $belongToProductId);

    /**
     * @param string $batchCode
     * @return void
     */
    public function setBatchCode(string $batchCode);

    /**
     * @param string $saleStartTime
     * @return void
     */
    public function setSaleStartTime(string $saleStartTime);

    /**
     * @param string $saleEndTime
     * @return void
     */
    public function setSaleEndTime(string $saleEndTime);

    /**
     * @param string|null $useStartTime
     * @return void
     */
    public function setUseStartTime(?string $useStartTime);

    /**
     * @param string|null $useEndTime
     * @return void
     */
    public function setUseEndTime(?string $useEndTime);

    /**
     * @param int|null $dueDays
     * @return void
     */
    public function setDueDays(?int $dueDays);
}
