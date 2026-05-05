<?php

declare(strict_types=1);

namespace Branch8\Report\Model;

use Branch8\Report\Api\Data\ProductChangeLogInterface;
use Magento\Framework\Model\AbstractModel;

class ProductChangeLog extends AbstractModel implements ProductChangeLogInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_init(ResourceModel\ProductChangeLog::class);
    }

    /**
     * @inheritdoc
     */
    public function getLogId(): ?int
    {
        $id = $this->getData(self::LOG_ID);
        return $id ? (int)$id : null;
    }

    /**
     * @inheritdoc
     */
    public function setLogId(int $logId): self
    {
        return $this->setData(self::LOG_ID, $logId);
    }

    /**
     * @inheritdoc
     */
    public function getProductId(): int
    {
        return (int)$this->getData(self::PRODUCT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setProductId(int $productId): self
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritdoc
     */
    public function getAction(): string
    {
        return (string)$this->getData(self::ACTION);
    }

    /**
     * @inheritdoc
     */
    public function setAction(string $action): self
    {
        return $this->setData(self::ACTION, $action);
    }

    /**
     * @inheritdoc
     */
    public function getPostData(): ?string
    {
        return $this->getData(self::POST_DATA);
    }

    /**
     * @inheritdoc
     */
    public function setPostData(string $postData): self
    {
        return $this->setData(self::POST_DATA, $postData);
    }

    /**
     * @inheritdoc
     */
    public function getBeforeValues(): ?string
    {
        return $this->getData(self::BEFORE_VALUES);
    }

    /**
     * @inheritdoc
     */
    public function setBeforeValues(string $beforeValues): self
    {
        return $this->setData(self::BEFORE_VALUES, $beforeValues);
    }

    /**
     * @inheritdoc
     */
    public function getAfterValues(): ?string
    {
        return $this->getData(self::AFTER_VALUES);
    }

    /**
     * @inheritdoc
     */
    public function setAfterValues(string $afterValues): self
    {
        return $this->setData(self::AFTER_VALUES, $afterValues);
    }

    /**
     * @inheritdoc
     */
    public function getUserType(): int
    {
        return (int)$this->getData(self::USER_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setUserType(int $userType): self
    {
        return $this->setData(self::USER_TYPE, $userType);
    }

    /**
     * @inheritdoc
     */
    public function getUserId(): ?int
    {
        $id = $this->getData(self::USER_ID);
        return $id ? (int)$id : null;
    }

    /**
     * @inheritdoc
     */
    public function setUserId(int $userId): self
    {
        return $this->setData(self::USER_ID, $userId);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getDebugBacktrace(): string
    {
        return (string)$this->getData(self::DEBUG_BACKTRACE);
    }

    /**
     * @inheritdoc
     */
    public function setDebugBacktrace(string $trace): self
    {
        return $this->setData(self::DEBUG_BACKTRACE, $trace);
    }

    public function beforeSave()
    {
        if ($this->isObjectNew() && empty($this->getData(self::DEBUG_BACKTRACE))) {
            $this->setData(self::DEBUG_BACKTRACE, json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)));
        }
        return parent::beforeSave();
    }
}
