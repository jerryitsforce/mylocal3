<?php

namespace Branch8\AppNotification\Api\Data;

interface NotificationMessageInterface
{
    /**
     * Implode array of tokens
     * @return string
     */
    public function getTokens(): string;

    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @return string
     */
    public function getBody(): string;

    /**
     * JSON encoded data
     * @return string
     */
    public function getData(): string;

    /**
     * JSON encoded additional data
     * @return string
     */
    public function getAdditionalData(): string;

    /**
     * @param string $tokens
     * @return $this
     */
    public function setTokens(string $tokens);


    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title);


    /**
     * @param string $body
     * @return $this
     */
    public function setBody(string $body);


    /**
     * @param string $data
     * @return $this
     */
    public function setData(string $data);


    /**
     * @param string $additionalData
     * @return $this
     */
    public function setAdditionalData(string $additionalData);
}
