<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Api\Data;

interface CheckResponseInterface
{
    /**
     * @return null|string
     */
    public function getReturnCode(): null|string;

    /**
     * @param string $status
     * @return self
     */
    public function setReturnCode(string $status): self;

    /**
     * @return null|string
     */
    public function getReturnMsg(): null|string;

    /**
     * @param string $message
     * @return self
     */
    public function setReturnMsg(string $message): self;

    /**
     * @return null|\Branch8\TicketApi\Api\Data\CheckDataInterface
     */
    public function getData(): null|\Branch8\TicketApi\Api\Data\CheckDataInterface;

    /**
     * @param \Branch8\TicketApi\Api\Data\CheckDataInterface $data
     * @return self
     */
    public function setData(\Branch8\TicketApi\Api\Data\CheckDataInterface $data): self;
}
