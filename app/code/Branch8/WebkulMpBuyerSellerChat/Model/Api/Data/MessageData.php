<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Api\Data;

use Branch8\WebkulMpBuyerSellerChat\Api\Data\MessageDataInterface;
use Webkul\MpBuyerSellerChat\Model\Message;

class MessageData extends Message implements MessageDataInterface
{
    private $isRead;

    protected function _construct()
    {
        $this->_init(
            \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\MessageData::class
        );
    }

    /**
     * @return array|mixed|string|null
     */
    public function getMesssageType()
    {
        return $this->getData(self::MESSAGE_TYPE);
    }

    /**
     * @param $messageType
     * @return \Branch8\WebkulMpBuyerSellerChat\Api\Data\CustomerDataInterface|MessageData
     */
    public function setMessageType($messageType)
    {
        return $this->setData(self::MESSAGE_TYPE, $messageType);
    }

    /**
     * @return array|\Magento\Framework\DataObject|mixed|null
     */
    public function getMeta()
    {
        return $this->getData(self::META);
    }

    /**
     * @param $value
     * @return MessageData|mixed
     */
    public function setMeta($value)
    {
        return $this->setData(self::META, $value);
    }

    /**
     * @param $uniqueId
     * @return mixed
     */
    public function loadByUniqueId($uniqueId)
    {
        return $this->getResource()->loadByUniqueID($this, $uniqueId);
    }

    /**
     * @return bool
     */
    public function isRead()
    {
        if (!$this->hasData('is_read')) {
            $connection = $this->getResource()->getConnection();
            $select = $connection->select()->from(
                'marketplace_chat_message_recipient', ['is_read']
            )->where('message_chat_history_id = ?', $this->getId());
            $select->reset(\Magento\Framework\DB\Select::COLUMNS)->columns(['is_read']);
            $row = $connection->fetchRow($select);
            $this->setData('is_read', $row ? (bool)$row['is_read'] : true);
        }
        return (bool)$this->getData('is_read');
    }

    /**
     * @param string $key
     * @param null $index
     * @return void
     */
    public function getData($key = '', $index = null)
    {
        $value = parent::getData($key, $index);
        if ($key === 'message' && $value) {
            return $this->decodeMessage($value);
        }
        return $value;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getMessage()
    {
        if ($this->getData('message') && in_array($this->getData('message_type'), ['text'])) {
            $this->setData('message', $this->decodeMessage($this->getData('message')));
        }
        return $this->getData('message');
    }

    /**
     * @param $str
     * @return mixed
     */
    private function decodeMessage($str)
    {
        if (preg_match('/\\\\u[0-9a-fA-F]{4}/', $str)) {
            $decoded = json_decode('"' . $str . '"');
            return $decoded !== null ? $decoded : $str;
        }
        return $str;
    }
}
