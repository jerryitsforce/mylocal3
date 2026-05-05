<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model;

use Branch8\HelpDesk\Api\Data\LessDataMessageInterface;
use Branch8\HelpDesk\Api\Data\MessageInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 *
 */
class Message extends AbstractExtensibleModel implements MessageInterface, LessDataMessageInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\HelpDesk\Model\ResourceModel\Message');
    }

    /**
     *  Get Messsage Id
     * @return array|mixed|string|null
     */
    public function getMessageId()
    {
        return $this->getData(self::MESSAGE_ID);
    }

    /**
     * @param $value
     * @return $this|MessageInterface
     */
    public function setMessageId($value)
    {
        $this->setData(self::MESSAGE_ID, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getTicketId()
    {
        return $this->getData(self::TICKET_ID);
    }

    /**
     * @param $value
     * @return $this|MessageInterface
     */
    public function setTicketId($value)
    {
        $this->setData(self::TICKET_ID, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getUserEmail()
    {
        return $this->getData(self::USER_EMAIL);
    }

    public function setUserEmail($value)
    {
        $this->setData(self::USER_EMAIL, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getUserName()
    {
        return $this->getData(self::USER_NAME);
    }

    /**
     * @param $value
     * @return $this|MessageInterface
     */
    public function setUserName($value)
    {
        $this->setData(self::USER_NAME, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getCustomerEmail()
    {
        return $this->getData(self::CUSTOMER_EMAIL);
    }

    /**
     * @param $value
     * @return $this|MessageInterface
     */

    public function setCustomerEmail($value)
    {
        $this->setData(self::CUSTOMER_EMAIL, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getCustomerName()
    {
        return $this->getData(self::CUSTOMER_NAME);
    }

    /**
     * @param $value
     * @return $this|MessageInterface
     */
    public function setCustomerName($value)
    {
        $this->setData(self::CUSTOMER_NAME, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getContent()
    {
        return $this->getData(self::CONTENT);
    }

    /**
     * @param $value
     * @return $this|MessageInterface
     */
    public function setContent($value)
    {
        $this->setData(self::CONTENT, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @param $value
     * @return $this|MessageInterface
     */
    public function setCreatedAt($value)
    {
        $this->setData(self::CREATED_AT, $value);
        return $this;
    }

    /**
     * @return \Magento\Framework\Api\ExtensionAttributesInterface
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @param \Branch8\HelpDesk\Api\Data\MessageExtensionInterface $extensionAttributes
     * @return Message
     */
    public function setExtensionAttributes(\Branch8\HelpDesk\Api\Data\MessageExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    /**
     * @return int
     */
    public function getBelongTo()
    {
        return (int)$this->getData(self::BELONG_TO);
    }

    /**
     * @param int $value
     * @return $this|MessageInterface
     */
    public function setBelongTo($value)
    {
        $this->setData(self::BELONG_TO, $value);
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRemoteAddress($value)
    {

        $this->setData(self::REMOTE_ADDRESS, $value);
        return $this;
    }

    /**
     * Get Is Read
     * @return int
     */
    public function getIsRead()
    {
        return (int)$this->getData(self::IS_READ);
    }

    /**
     * set Is Read
     * @return Message
     */
    public function setIsRead($value)
    {
        $this->setData(self::IS_READ, $value);
        return $this;
    }

}
