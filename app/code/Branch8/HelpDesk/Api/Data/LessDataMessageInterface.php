<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Api\Data;
/**
 * Less Message Data not include sensitive information
 */
interface LessDataMessageInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * @param string $value
     * @return LessDataMessageInterface
     */
    public function setMessageId($value);

    /**
     * @return string
     */
    public function getMessageId();

    /**
     * @param string $value
     * @return LessDataMessageInterface
     */
    public function setBelongTo($value);

    /**
     * @return string
     */
    public function getBelongTo();
    /**
     * Get user_email
     * @return string|null
     */
    public function getUserEmail();

    /**
     * Set user_email
     * @param string $value
     * @return \Branch8\HelpDesk\Api\Data\MessageInterface
     */
    public function setUserEmail($value);

    /**
     * Get user_name
     * @return string|null
     */
    public function getUserName();

    /**
     * Set user_name
     * @param string $value
     * @return \Branch8\HelpDesk\Api\Data\MessageInterface
     */
    public function setUserName($value);

    /**
     * Get customer_email
     * @return string|null
     */
    public function getCustomerEmail();

    /**
     * Set customer_email
     * @param string $value
     * @return \Branch8\HelpDesk\Api\Data\MessageInterface
     */
    public function setCustomerEmail($value);

    /**
     * Get customer_name
     * @return string|null
     */
    public function getCustomerName();

    /**
     * Set customer_name
     * @param string $value
     * @return \Branch8\HelpDesk\Api\Data\MessageInterface
     */
    public function setCustomerName($value);

    /**
     * Get Content
     * @return string|null
     */
    public function getContent();

    /**
     * Set content
     * @param string $value
     * @return \Branch8\HelpDesk\Api\Data\MessageInterface
     */
    public function setContent($value);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $value
     * @return \Branch8\HelpDesk\Api\Data\MessageInterface
     */
    public function setCreatedAt($value);

    /**
     * Set Is Read
     * @param $value
     * @return MessageInterface
     */
    public function setIsRead($value);

    /**
     * Get Is Read
     * @return int
     */
    public function getIsRead();

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Branch8\HelpDesk\Api\Data\LessDataMessageExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Branch8\HelpDesk\Api\Data\LessDataMessageExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Branch8\HelpDesk\Api\Data\MessageExtensionInterface $extensionAttributes);
}

