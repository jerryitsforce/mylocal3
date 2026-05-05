<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model\Api\Data;

use Branch8\WebkulMpBuyerSellerChat\Api\Data\MessageMetaInformationInterface;
use Magento\Framework\DataObject;

class MessageMetaInformation extends DataObject implements MessageMetaInformationInterface
{
    const KEY = 'key';
    const VALUE = 'value';

    /**
     * @param string $key
     * @return MessageMetaInformationInterface|MessageMetaInformation
     */
    public function setKey(string $key)
    {
        return $this->setData(self::KEY, $key);
    }

    /**
     * @param $value
     * @return MessageMetaInformationInterface|MessageMetaInformation
     */
    public function setValue($value)
    {
        return $this->setData(self::VALUE, $value);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getKey()
    {
        return $this->getData(self::KEY);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getValue()
    {
        return $this->getData(self::VALUE);
    }
}
