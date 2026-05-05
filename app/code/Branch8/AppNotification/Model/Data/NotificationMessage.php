<?php

namespace Branch8\AppNotification\Model\Data;

use Branch8\AppNotification\Api\Data\NotificationMessageInterface;

/**
 * Used for message queue
 */
class NotificationMessage implements NotificationMessageInterface
{
    protected string $tokens = '';
    protected string $title = '';
    protected string $body = '';
    protected string $data = '';
    protected string $additionalData = '';

    public function getTokens(): string
    {
        return $this->tokens;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getAdditionalData(): string
    {
        return $this->additionalData;
    }

    public function setTokens(string $tokens)
    {
        $this->tokens = $tokens;
        return $this;
    }
    public function setTitle(string $title)
    {
        $this->title = $title;
        return $this;
    }
    public function setBody(string $body)
    {
        $this->body = $body;
        return $this;
    }
    public function setData(string $data)
    {
        $this->data = $data;
        return $this;
    }

    public function setAdditionalData(string $additionalData)
    {
        $this->additionalData = $additionalData;
        return $this;
    }
}
