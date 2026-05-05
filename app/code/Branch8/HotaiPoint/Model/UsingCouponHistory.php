<?php

namespace Branch8\HotaiPoint\Model;

use Branch8\HotaiPoint\Api\Data\UsingCouponHistoryInterface as ModelInterface;
use Branch8\HotaiPoint\Model\ResourceModel\UsingCouponHistory as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class UsingCouponHistory extends AbstractModel implements ModelInterface
{
    const TABLE_NAME    = "hotai_point_using_coupon_history";
    const ID_FIELD_NAME = "record_id";

    const RECORD_ID   = "record_id";
    const CUSTOMER_ID = "customer_id";
    const TRANS_S_N   = "trans_s_n";
    const TRANS_AT    = "trans_at";
    const COUPON_NO   = "coupon_no";
    const IS_SUCCESS  = "is_success";
    const MEMO        = "memo";
    const CREATED_AT  = "created_at";
    const UPDATED_AT  = "updated_at";

    protected function _construct()
    {
        $this->_init(
            ResourceModel::class
        );
    }

    public function getId(): ?int
    {
        return $this->getData(self::RECORD_ID);
    }

    public function getCustomerId(): int
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    public function setCustomerId(int|string $customerId)
    {
        $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getTransSN(): string
    {
        return $this->getData(self::TRANS_S_N);
    }

    public function setTransSN(string $transSN)
    {
        $this->setData(self::TRANS_S_N, $transSN);
    }

    public function getTransAt(): string
    {
        return $this->getData(self::TRANS_AT);
    }

    public function setTransAt(string $transAt)
    {
        $this->setData(self::TRANS_AT, $transAt);
    }

    public function getCouponNo(): string
    {
        return $this->getData(self::TRANS_AT);
    }

    public function setCouponNo(string $couponNo)
    {
        $this->setData(self::COUPON_NO, $couponNo);
    }

    public function getIsSuccess(): int
    {
        return $this->getData(self::IS_SUCCESS);
    }

    public function setIsSuccess(int|string $isSuccess)
    {
        $this->setData(self::IS_SUCCESS, $isSuccess);
    }

    public function getMemo(): ?string
    {
        return $this->getData(self::MEMO);
    }

    public function setMemo(string $memo)
    {
        $this->setData(self::MEMO, $memo);
    }

    public function getCreatedAt(): string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function getUpdatedAt(): string
    {
        return $this->getData(self::UPDATED_AT);
    }
}
