<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model\Data;

use HotaiConnected\Logistics\Api\Data\PickupResponseInterface;
use Magento\Framework\DataObject;

/**
 * Pickup Response Data Model
 */
class PickupResponse extends DataObject implements PickupResponseInterface
{
    /**
     * @inheritDoc
     */
    public function isSuccess()
    {
        return (bool)$this->getData('success');
    }

    /**
     * @inheritDoc
     */
    public function setSuccess($success)
    {
        return $this->setData('success', $success);
    }

    /**
     * @inheritDoc
     */
    public function getWaybills()
    {
        return $this->getData('waybills') ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setWaybills(array $waybills)
    {
        return $this->setData('waybills', $waybills);
    }

    /**
     * @inheritDoc
     */
    public function getMessage()
    {
        return $this->getData('message');
    }

    /**
     * @inheritDoc
     */
    public function setMessage($message)
    {
        return $this->setData('message', $message);
    }

    /**
     * @inheritDoc
     */
    public function getErrorMessage()
    {
        return $this->getData('error_message');
    }

    /**
     * @inheritDoc
     */
    public function setErrorMessage($errorMessage)
    {
        return $this->setData('error_message', $errorMessage);
    }
}
