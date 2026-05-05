<?php

declare(strict_types=1);

namespace Branch8\Edenred\Model\Api\Data;

use Branch8\Edenred\Api\Data\ReceiveNotificationApiResponseInterface;

class ReceiveNotificationApiResponse implements ReceiveNotificationApiResponseInterface
{
    const DATA_KEY_RETURN_CODE = "returnCode";
    const DATA_KEY_RETURN_MESSAGE = "returnMessage";

    protected $_data = [];

    /**
     * @inheritDoc
     */
    public function getReturnCode(): null|string
    {
        return $this->_data[self::DATA_KEY_RETURN_CODE] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setReturnCode(string $returnCode): ReceiveNotificationApiResponseInterface
    {
        $this->_data[self::DATA_KEY_RETURN_CODE] = $returnCode;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getReturnMessage(): null|string
    {
        return $this->_data[self::DATA_KEY_RETURN_MESSAGE] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setReturnMessage(string $message): ReceiveNotificationApiResponseInterface
    {
        $this->_data[self::DATA_KEY_RETURN_MESSAGE] = $message;

        return $this;
    }
}
