<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Api\Data;

/**
 * Pickup Response Data Interface
 */
interface PickupResponseInterface
{
    /**
     * Get success status
     *
     * @return bool
     */
    public function isSuccess();

    /**
     * Set success status
     *
     * @param bool $success
     * @return $this
     */
    public function setSuccess($success);

    /**
     * Get waybill records
     *
     * @return array
     */
    public function getWaybills();

    /**
     * Set waybill records
     *
     * @param array $waybills
     * @return $this
     */
    public function setWaybills(array $waybills);

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage();

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * Get error message
     *
     * @return string|null
     */
    public function getErrorMessage();

    /**
     * Set error message
     *
     * @param string $errorMessage
     * @return $this
     */
    public function setErrorMessage($errorMessage);
}
