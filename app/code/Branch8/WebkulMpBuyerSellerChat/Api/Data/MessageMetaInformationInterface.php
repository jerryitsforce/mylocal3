<?php

namespace Branch8\WebkulMpBuyerSellerChat\Api\Data;

interface MessageMetaInformationInterface
{
    /**
     * @param string $key
     * @return MessageMetaInformationInterface
     */
    public function setKey(string $key);

    /**
     * @param $value
     * @return MessageMetaInformationInterface
     */
    public function setValue($value);

    /**
     * @return string
     */
    public function getKey();
    /**
     * @return string
     */
    public function getValue();
}
