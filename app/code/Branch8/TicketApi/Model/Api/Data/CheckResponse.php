<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Model\Api\Data;

use Branch8\TicketApi\Api\Data\CheckResponseInterface as ResponseInterface;
use Branch8\TicketApi\Api\Data\CheckDataInterface as DataInterface;

class CheckResponse implements ResponseInterface
{
    const INDEX_RETURN_CODE = "returnCode";
    const INDEX_RETURN_MSG  = "returnMsg";
    const INDEX_RETURN_DATA = "data";

    /**
     * @var \Branch8\TicketApi\Api\Data\CheckResponseInterface
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
    public function setReturnCode(string $returnCode): ResponseInterface
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
    public function setReturnMsg(string $message): ResponseInterface
    {
        $this->_data[self::INDEX_RETURN_MSG] = $message;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getData(): null|DataInterface
    {
        return $this->_data[self::INDEX_RETURN_DATA] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setData(DataInterface $data): ResponseInterface
    {
        $this->_data[self::INDEX_RETURN_DATA] = $data;

        return $this;
    }
}
