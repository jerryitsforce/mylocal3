<?php

namespace Branch8\HotaiPoint\Model;

use Branch8\HotaiPoint\Api\Data\HotaiPointApiRecordInterface;
use Magento\Framework\Model\AbstractModel;

class HotaiPointApiRecord extends AbstractModel implements HotaiPointApiRecordInterface
{
    const RECORD_ID        = "record_id";
    const TRANS_TYPE       = "trans_type";
    const TRACE_NO         = "trace_no";
    const BU_NO            = "bu_no";
    const ONEID            = "oneid";
    const MEMBER_ACCOUNT   = "member_account";
    const ONEID_TYPE       = "oneid_type";
    const RS_NO            = "rs_no";
    const POS_NO           = "pos_no";
    const TRANS_S_N        = "trans_s_n";
    const TRANS_DATETIME   = "trans_datetime";
    const TRANS_DESC       = "trans_desc";
    const ADD_TYPE         = "add_type";
    const AMT              = "amt";
    const POINT_AMT        = "point_amt";
    const DEDUCTION_POINT  = "deduction_point";
    const ACTIVITY_CODES   = "activity_codes";
    const SOURCE_TRACE_NO  = "source_trace_no";
    const APP_ID           = "app_id";
    const ADD_POINT        = "add_point";
    const POINT_VALID_TYPE = "point_valid_type";
    const POINT_VALID_DATE = "point_valid_date";
    const COMMIT_STATUS    = "commit_status";
    const COMMIT_DATETIME  = "commit_datetime";
    const SYNC_STATUS      = "sync_status";
    const CREATED_AT       = "created_at";
    const UPDATED_AT       = "updated_at";

    const TRANS_TYPE_DEDUCTION_POINT       = 1; // 交易類型: 即時兌點
    const TRANS_TYPE_ADD_N_DEDUCTION_POINT = 2; // 交易類型: 即時累兌點
    const TRANS_TYPE_ADD_POINT             = 3; // 交易類型: 即時累點
    const TRANS_TYPE_BATCH_ADD_POINT       = 4; // 交易類型: 批次累點
    const TRANS_TYPE_RETURN_POINT          = 5; // 交易類型: 即時退貨
    const TRANS_TYPE_ADD_POINT_NON         = 6; // 交易類型: 無金額即時累點
    const TRANS_TYPE_VALID_POOL            = [1, 2, 3, 4, 5, 6];

    // not done
    const TRANS_TYPE_NEED_RS_NO = [1, 2, 3, 4, 6];
    const TRANS_TYPE_NEED_POS_NO = [1, 2, 3, 4, 6];
    const TRANS_TYPE_NEED_ADD_TYPE = [2, 3, 6];
    const TRANS_TYPE_NEED_AMT = [2, 3, 4];
    const TRANS_TYPE_NEED_POINT_AMT = [2, 3, 4];
    const TRANS_TYPE_NEED_DEDUCTION_POINT = [1, 2];
    const TRANS_TYPE_NEED_ACTIVITY_CODES = [2, 3, 4];
    const TRANS_TYPE_NEED_SOURCE_TRACE_NO = [5];
    const TRANS_TYPE_NEED_APP_ID = [4];
    const TRANS_TYPE_NEED_ADD_POINT = [6];
    const TRANS_TYPE_NEED_POINT_VALID_TYPE = [6];
    const TRANS_TYPE_NEED_POINT_VALID_DATE = [6];

    const ONEID_TYPE_NATURAL_PERSON = "P"; // 會員類別: 自然人
    const ONEID_TYPE_LEGAL_PERSON   = "C"; // 會員類別: 法人
    const ONEID_TYPE_VALID_POOL     = ["P", "C"];

    const ADD_TYPE_NORMAL         = "1"; // 累點類型: 一般交易累點 / 行銷贈點
    const ADD_TYPE_PURCHASE_POINT = "2"; // 累點類型: 點數購買(未開放使用)
    const ADD_TYPE_VALID_POOL     = ["1"];

    const POINT_VALID_TYPE_NORMAL     = "1"; // 點數效期類型: 一般點數效期
    const POINT_VALID_TYPE_CUSTOM     = "2"; // 點數效期類型: 自訂點數效期
    const POINT_VALID_TYPE_VALID_POOL = ["1", "2"];

    const COMMIT_STATUS_YET_COMMITED = 0; // 交易commit狀態: 未commit
    const COMMIT_STATUS_COMMITED     = 1; // 交易commit狀態: 已commit
    const COMMIT_STATUS_VALID_POOL   = [0, 1];

    const SYNC_STATUS_YET_SYNCED = 0; // 每日交易總檔同步狀態: 未同步
    const SYNC_STATUS_SYNCED     = 1; // 每日交易總檔同步狀態: 已同步
    const SYNC_STATUS_VALID_POOL = [0, 1];

    protected function _construct()
    {
        $this->_init(
            \Branch8\HotaiPoint\Model\ResourceModel\HotaiPointApiRecord::class
        );
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->getData(self::RECORD_ID);
    }

    /**
     * @inheritDoc
     */
    public function getTransType(): ?int
    {
        return $this->getData(self::TRANS_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setTransType(int $transType)
    {
        if (!in_array($transType, self::TRANS_TYPE_VALID_POOL)) {
            throw new \Exception("Input trans type is not valid: " . $transType);
        }

        $this->setData(self::TRANS_TYPE, $transType);
    }

    /**
     * @inheritDoc
     */
    public function getTraceNo(): ?string
    {
        return $this->getData(self::TRACE_NO);
    }

    /**
     * @inheritDoc
     */
    public function setTraceNo(string $transNo)
    {
        $this->setData(self::TRACE_NO, $transNo);
    }

    /**
     * @inheritDoc
     */
    public function getBuNo(): ?string
    {
        return $this->getData(self::BU_NO);
    }

    /**
     * @inheritDoc
     */
    public function setBuNo(string $buNo)
    {
        $this->setData(self::BU_NO, $buNo);
    }

    /**
     * @inheritDoc
     */
    public function getOneid(): ?string
    {
        return $this->getData(self::ONEID);
    }

    /**
     * @inheritDoc
     */
    public function setOneid(string $oneid)
    {
        $this->setData(self::ONEID, $oneid);
    }

    /**
     * @inheritDoc
     */
    public function getMemberAccount(): ?string
    {
        return $this->getData(self::MEMBER_ACCOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setMemberAccount(string $memberAccount)
    {
        $this->setData(self::MEMBER_ACCOUNT, $memberAccount);
    }

    /**
     * @inheritDoc
     */
    public function getOneidType(): ?string
    {
        return $this->getData(self::ONEID_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setOneidType(string $oneidType)
    {
        if (!in_array($oneidType, self::ONEID_TYPE_VALID_POOL)) {
            throw new \Exception("Input oneid type is not valid: " . $oneidType);
        }

        $this->setData(self::ONEID_TYPE, $oneidType);
    }

    /**
     * @inheritDoc
     */
    public function getRsNo(): ?string
    {
        return $this->getData(self::RS_NO);
    }

    /**
     * @inheritDoc
     */
    public function setRsNo(string $rsNo)
    {
        $this->setData(self::RS_NO, $rsNo);
    }

    /**
     * @inheritDoc
     */
    public function getPosNo(): ?string
    {
        return $this->getData(self::POS_NO);
    }

    /**
     * @inheritDoc
     */
    public function setPosNo(string $posNo)
    {
        $this->setData(self::POS_NO, $posNo);
    }

    /**
     * @inheritDoc
     */
    public function getTransSN(): ?string
    {
        return $this->getData(self::TRANS_S_N);
    }

    /**
     * @inheritDoc
     */
    public function setTransSN(string $transSN)
    {
        $this->setData(self::TRANS_S_N, $transSN);
    }

    /**
     * @inheritDoc
     */
    public function getTransDatetime(): ?string
    {
        return $this->getData(self::TRANS_DATETIME);
    }

    /**
     * @inheritDoc
     */
    public function setTransDatetime(string $transDatetime)
    {
        $this->setData(self::TRANS_DATETIME, $transDatetime);
    }

    /**
     * @inheritDoc
     */
    public function getTransDesc(): ?string
    {
        return $this->getData(self::TRANS_DESC);
    }

    /**
     * @inheritDoc
     */
    public function setTransDesc(string $transDesc)
    {
        $this->setData(self::TRANS_DESC, $transDesc);
    }

    /**
     * @inheritDoc
     */
    public function getAddType(): ?string
    {
        return $this->getData(self::ADD_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setAddType(string $addType)
    {
        if (!in_array($addType, self::ADD_TYPE_VALID_POOL)) {
            throw new \Exception("Input add type is not valid: " . $addType);
        }

        $this->setData(self::ADD_TYPE, $addType);
    }

    /**
     * @inheritDoc
     */
    public function getAmt(): ?int
    {
        return $this->getData(self::AMT);
    }

    /**
     * @inheritDoc
     */
    public function setAmt(int $amt)
    {
        $this->setData(self::AMT, $amt);
    }

    /**
     * @inheritDoc
     */
    public function getPointAmt(): ?int
    {
        return $this->getData(self::POINT_AMT);
    }

    /**
     * @inheritDoc
     */
    public function setPointAmt(int $pointAmt)
    {
        $this->setData(self::POINT_AMT, $pointAmt);
    }

    /**
     * @inheritDoc
     */
    public function getDeductionPoint(): ?int
    {
        return $this->getData(self::DEDUCTION_POINT);
    }

    /**
     * @inheritDoc
     */
    public function setDeductionPoint(int $deductionPoint)
    {
        $this->setData(self::DEDUCTION_POINT, $deductionPoint);
    }

    /**
     * @inheritDoc
     */
    public function getActivityCodes(): ?string
    {
        return $this->getData(self::ACTIVITY_CODES);
    }

    /**
     * @inheritDoc
     */
    public function setActivityCodes(string $activityCodes)
    {
        $this->setData(self::ACTIVITY_CODES, $activityCodes);
    }

    /**
     * @inheritDoc
     */
    public function getSourceTraceNo(): ?string
    {
        return $this->getData(self::SOURCE_TRACE_NO);
    }

    /**
     * @inheritDoc
     */
    public function setSourceTraceNo(string $sourceTraceNo)
    {
        $this->setData(self::SOURCE_TRACE_NO, $sourceTraceNo);
    }

    /**
     * @inheritDoc
     */
    public function getAppId(): ?string
    {
        return $this->getData(self::APP_ID);
    }

    /**
     * @inheritDoc
     */
    public function setAppId(string $appId)
    {
        $this->setData(self::APP_ID, $appId);
    }

    /**
     * @inheritDoc
     */
    public function getAddPoint(): ?int
    {
        return $this->getData(self::ADD_POINT);
    }

    /**
     * @inheritDoc
     */
    public function setAddPoint(int $addPoint)
    {
        $this->setData(self::ADD_POINT, $addPoint);
    }

    /**
     * @inheritDoc
     */
    public function getPointValidType(): ?string
    {
        return $this->getData(self::POINT_VALID_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setPointValidType(string $pointValidType)
    {
        if (!in_array($pointValidType, self::POINT_VALID_TYPE_VALID_POOL)) {
            throw new \Exception("Input point valid type is not valid: " . $pointValidType);
        }

        $this->setData(self::POINT_VALID_TYPE, $pointValidType);
    }

    /**
     * @inheritDoc
     */
    public function getPointValidDate(): ?string
    {
        return $this->getData(self::POINT_VALID_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setPointValidDate(string $pointValidDate)
    {
        $this->setData(self::POINT_VALID_DATE, $pointValidDate);
    }

    /**
     * @inheritDoc
     */
    public function getCommitStatus(): ?int
    {
        return $this->getData(self::COMMIT_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setCommitStatus(int $commitStatus)
    {
        if (!in_array($commitStatus, self::COMMIT_STATUS_VALID_POOL)) {
            throw new \Exception("Input commit status is not valid: " . $commitStatus);
        }

        $this->setData(self::COMMIT_STATUS, $commitStatus);
    }

    /**
     * @inheritDoc
     */
    public function getCommitDatetime(): ?string
    {
        return $this->getData(self::COMMIT_DATETIME);
    }

    /**
     * @inheritDoc
     */
    public function setCommitDatetime(string $commitDatetime)
    {
        $this->setData(self::COMMIT_DATETIME, $commitDatetime);
    }

    /**
     * @inheritDoc
     */
    public function getSyncStatus(): ?int
    {
        return $this->getData(self::SYNC_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setSyncStatus(int $syncStatus)
    {
        if (!in_array($syncStatus, self::SYNC_STATUS_VALID_POOL)) {
            throw new \Exception("Input sync status is not valid: " . $syncStatus);
        }

        $this->setData(self::SYNC_STATUS, $syncStatus);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }
}
