<?php

declare(strict_types=1);

namespace Branch8\Report\Api\Data;

interface ProductChangeLogInterface
{
    /**#@+
     * Constants for keys of data array.
     */
    public const LOG_ID = 'log_id';
    public const PRODUCT_ID = 'product_id';
    public const ACTION = 'action';
    public const POST_DATA = 'post_data';
    public const BEFORE_VALUES = 'before_values';
    public const AFTER_VALUES = 'after_values';
    public const USER_TYPE = 'user_type';
    public const USER_ID = 'user_id';
    public const CREATED_AT = 'created_at';
    public const DEBUG_BACKTRACE = 'debug_backtrace';
    /**#@-*/

    /**
     * Returns log ID.
     *
     * @return int|null
     */
    public function getLogId(): ?int;

    /**
     * Sets the log ID.
     *
     * @param int $logId
     *
     * @return $this
     */
    public function setLogId(int $logId): self;

    /**
     * Returns product ID.
     *
     * @return int
     */
    public function getProductId(): int;

    /**
     * Sets the product ID.
     *
     * @param int $productId
     *
     * @return $this
     */
    public function setProductId(int $productId): self;

    /**
     * Returns action.
     *
     * @return string
     */
    public function getAction(): string;

    /**
     * Sets the action.
     *
     * @param string $action
     *
     * @return $this
     */
    public function setAction(string $action): self;

    /**
     * Returns post data.
     *
     * @return string|null
     */
    public function getPostData(): ?string;

    /**
     * Sets the post data.
     *
     * @param string $postData
     *
     * @return $this
     */
    public function setPostData(string $postData): self;

    /**
     * Returns before values.
     *
     * @return string|null
     */
    public function getBeforeValues(): ?string;

    /**
     * Sets the before values.
     *
     * @param string $beforeValues
     *
     * @return $this
     */
    public function setBeforeValues(string $beforeValues): self;

    /**
     * Returns after values.
     *
     * @return string|null
     */
    public function getAfterValues(): ?string;

    /**
     * Sets the after values.
     *
     * @param string $afterValues
     *
     * @return $this
     */
    public function setAfterValues(string $afterValues): self;

    /**
     * Returns user type.
     *
     * @return int
     */
    public function getUserType(): int;

    /**
     * Sets the user type.
     *
     * @param int $userType
     *
     * @return $this
     */
    public function setUserType(int $userType): self;

    /**
     * Returns user ID.
     *
     * @return int|null
     */
    public function getUserId(): ?int;

    /**
     * Sets the user ID.
     *
     * @param int $userId
     *
     * @return $this
     */
    public function setUserId(int $userId): self;

    /**
     * Returns the created at.
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Sets the created at.
     *
     * @param string $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Returns the debug backtrace.
     *
     * @return string
     */
    public function getDebugBacktrace(): string;

    /**
     * Sets the debug backtrace.
     *
     * @param string $trace
     *
     * @return $this
     */
    public function setDebugBacktrace(string $trace): self;
}

