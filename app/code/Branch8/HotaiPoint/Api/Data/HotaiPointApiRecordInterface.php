<?php

namespace Branch8\HotaiPoint\Api\Data;

interface HotaiPointApiRecordInterface
{
    public function getId(): ?int;

    public function getTransType(): ?int;
    public function setTransType(int $transType);
    public function getTraceNo(): ?string;
    public function setTraceNo(string $traceNo);
    public function getBuNo(): ?string;
    public function setBuNo(string $buNo);
    public function getOneid(): ?string;
    public function setOneid(string $oneid);
    public function getMemberAccount(): ?string;
    public function setMemberAccount(string $memberAccount);
    public function getOneidType(): ?string;
    public function setOneidType(string $oneidType);
    public function getRsNo(): ?string;
    public function setRsNo(string $rsNo);
    public function getPosNo(): ?string;
    public function setPosNo(string $posNo);
    public function getTransSN(): ?string;
    public function setTransSN(string $transSN);
    public function getTransDatetime(): ?string;
    public function setTransDatetime(string $transDatetime);
    public function getTransDesc(): ?string;
    public function setTransDesc(string $transDesc);
    public function getAddType(): ?string;
    public function setAddType(string $addType);
    public function getAmt(): ?int;
    public function setAmt(int $amt);
    public function getPointAmt(): ?int;
    public function setPointAmt(int $pointAmt);
    public function getDeductionPoint(): ?int;
    public function setDeductionPoint(int $deductionPoint);
    public function getActivityCodes(): ?string;
    public function setActivityCodes(string $activityCodes);
    public function getSourceTraceNo(): ?string;
    public function setSourceTraceNo(string $sourceTraceNo);
    public function getAppId(): ?string;
    public function setAppId(string $appId);
    public function getAddPoint(): ?int;
    public function setAddPoint(int $addPoint);
    public function getPointValidType(): ?string;
    public function setPointValidType(string $pointValidType);
    public function getPointValidDate(): ?string;
    public function setPointValidDate(string $pointValidDate);

    public function getCommitStatus(): ?int;
    public function setCommitStatus(int $commitStatus);
    public function getCommitDatetime(): ?string;
    public function setCommitDatetime(string $commitDatetime);
    public function getSyncStatus(): ?int;
    public function setSyncStatus(int $syncStatus);
    public function getCreatedAt(): ?string;
    public function getUpdatedAt(): ?string;
}
