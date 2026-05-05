<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Model\Api\Data;

use Branch8\TicketApi\Api\Data\NotifyResponseInterface;
use Branch8\TicketApi\Api\Data\NotifyDataInterface;

class NotifyResponse implements NotifyResponseInterface
{
    const INDEX_RETURN_CODE = "returnCode";
    const INDEX_RETURN_MSG  = "returnMsg";
    const INDEX_RETURN_DATA = "data";

    /**
     * @var \Branch8\TicketApi\Api\Data\NotifyResponseInterface
     */
    protected $_data = [];

    /**
     * @inheritDoc
     */
    public function getReturnCode(): null|string
    {
        return $this->_data[self::INDEX_RETURN_CODE] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setReturnCode(string $returnCode): NotifyResponseInterface
    {
        $this->_data[self::INDEX_RETURN_CODE] = $returnCode;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getReturnMsg(): null|string
    {
        return $this->_data[self::INDEX_RETURN_MSG] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setReturnMsg(string $message): NotifyResponseInterface
    {
        $this->_data[self::INDEX_RETURN_MSG] = $message;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getData(): null|NotifyDataInterface
    {
        return $this->_data[self::INDEX_RETURN_DATA] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setData(NotifyDataInterface $data): NotifyResponseInterface
    {
        $this->_data[self::INDEX_RETURN_DATA] = $data;

        return $this;
    }
}
