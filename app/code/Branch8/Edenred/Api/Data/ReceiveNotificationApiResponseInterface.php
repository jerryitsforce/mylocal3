<?php

declare(strict_types=1);

namespace Branch8\Edenred\Api\Data;

interface ReceiveNotificationApiResponseInterface
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
    public function getReturnMessage(): null|string;

    /**
     * @param string $message
     * @return self
     */
    public function setReturnMessage(string $message): self;
}
