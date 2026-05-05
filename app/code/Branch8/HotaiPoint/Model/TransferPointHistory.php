<?php

namespace Branch8\HotaiPoint\Model;

use Branch8\HotaiPoint\Api\Data\TransferPointHistoryInterface as ModelInterface;
use Branch8\HotaiPoint\Model\ResourceModel\TransferPointHistory as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class TransferPointHistory extends AbstractModel implements ModelInterface
{
    const TABLE_NAME    = "hotai_point_transfer_point_history";
    const ID_FIELD_NAME = "record_id";

    const RECORD_ID                    = "record_id";
    const FROM_CUSTOMER_ID             = "from_customer_id";
    const FROM_CUSTOMER_ONEID          = "from_customer_oneid";
    const FROM_CUSTOMER_MEMBER_ACCOUNT = "from_customer_member_account";
    const TO_CUSTOMER_ONEID            = "to_customer_oneid";
    const TO_CUSTOMER_MEMBER_ACCOUNT   = "to_customer_member_account";
    const TRANSFER_POINT               = "transfer_point";
    const TRANS_S_N                    = "trans_s_n";
    const TRANS_AT                     = "trans_at";
    const IS_SUCCESS                   = "is_success";
    const MEMO                         = "memo";
    const CREATED_AT                   = "created_at";
    const UPDATED_AT                   = "updated_at";

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

    public function getFromCustomerId(): int
    {
        return $this->getData(self::FROM_CUSTOMER_ID);
    }

    public function setFromCustomerId(int|string $fromCustomerId)
    {
        $this->setData(self::FROM_CUSTOMER_ID, $fromCustomerId);
    }

    public function getFromCustomerOneid(): string
    {
        return $this->getData(self::FROM_CUSTOMER_ONEID);
    }

    public function setFromCustomerOneid(string $fromCustomerOneid)
    {
        $this->setData(self::FROM_CUSTOMER_ONEID, $fromCustomerOneid);
    }

    public function getFromCustomerMemberAccount(): string
    {
        return $this->getData(self::FROM_CUSTOMER_MEMBER_ACCOUNT);
    }

    public function setFromCustomerMemberAccount(string $fromCustomerMemberAccount)
    {
        $this->setData(self::FROM_CUSTOMER_MEMBER_ACCOUNT, $fromCustomerMemberAccount);
    }

    public function getToCustomerOneid(): string
    {
        return $this->getData(self::TO_CUSTOMER_ONEID);
    }

    public function setToCustomerOneid(string $toCustomerOneid)
    {
        $this->setData(self::TO_CUSTOMER_ONEID, $toCustomerOneid);
    }

    public function getToCustomerMemberAccount(): string
    {
        return $this->getData(self::TO_CUSTOMER_MEMBER_ACCOUNT);
    }

    public function setToCustomerMemberAccount(string $toCustomerMemberAccount)
    {
        $this->setData(self::TO_CUSTOMER_MEMBER_ACCOUNT, $toCustomerMemberAccount);
    }

    public function getTransferPoint(): int
    {
        return $this->getData(self::TRANSFER_POINT);
    }

    public function setTransferPoint(int|string $transferPoint)
    {
        $this->setData(self::TRANSFER_POINT, $transferPoint);
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
