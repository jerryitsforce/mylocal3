<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Api\Data;
/**
 * Message Interface
 */
interface MessageInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    const IS_READ = 'is_read';
    const MESSAGE_ID = 'message_id';
    const CONTENT = 'content';
    const CUSTOMER_ID = 'customer_id';
    const CUSTOMER_NAME = 'customer_name';
    const CUSTOMER_EMAIL = 'customer_email';
    const TYPE = 'type';
    const USER_ID = 'user_id';
    const UPDATED_AT = 'updated_at';
    const USER_NAME = 'user_name';
    const USER_EMAIL = 'user_email';
    const TICKET_ID = 'ticket_id';
    const BODY = 'body';
    const CREATED_AT = 'created_at';
    const BELONG_TO = 'belong_to';
    const REMOTE_ADDRESS = 'remote_ip';

    /**
     * Get Message id
     * @return string|null
     */
    public function getMessageId();

    /**
     * Set Message Id
     * @param string $value
     * @return \Branch8\HelpDesk\Api\Data\MessageInterface
     */
    public function setMessageId($value);

    /**
     * Get ticket_id
     * @return string|null
     */
    public function getTicketId();

    /**
     * Set ticket_id
     * @param string $value
     * @return \Branch8\HelpDesk\Api\Data\MessageInterface
     */
    public function setTicketId($value);

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
     * Get belong_to
     * @return int|null
     */
    public function getBelongTo();

    /**
     * Set belong_to
     * @param int $value
     * @return \Branch8\HelpDesk\Api\Data\MessageInterface
     */
    public function setBelongTo(int $value);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Branch8\HelpDesk\Api\Data\MessageExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Branch8\HelpDesk\Api\Data\MessageExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Branch8\HelpDesk\Api\Data\MessageExtensionInterface $extensionAttributes);
}

