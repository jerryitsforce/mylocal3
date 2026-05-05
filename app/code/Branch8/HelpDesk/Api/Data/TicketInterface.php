<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Api\Data;

use Branch8\HelpDesk\Model\Ticket;
use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Ticket Interface
 */
interface TicketInterface extends ExtensibleDataInterface
{
    const TITLE='title';

    const ORDER = 'order';

    const PHONE = 'phone';

    const PHONE_MASKED = 'phone_masked';
    const LAST_REPLY_NAME = 'last_reply_name';

    const CATEGORY_ID = 'category_id';

    const UPDATED_AT = 'updated_at';

    const CUSTOMER_EMAIL = 'customer_email';

    const CUSTOMER_EMAIL_MASKED = 'customer_email_masked';

    const PRODUCT_ID = 'product_id';

    const ORDER_ID = 'order_id';

    const CREATED_AT = 'created_at';

    const CUSTOMER_ID = 'customer_id';

    const CUSTOMER_NAME = 'customer_name';

    const CONTENT = 'content';

    const LAST_REPLY_AT = 'last_reply_date';

    const CODE = 'code';

    const STATUS_ID = 'status';

    const PRIORITY_ID = 'priority';

    const USER_ID = 'user_id';

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param $value
     * @return TicketInterface
     */
    public function setTitle($value);

    /**
     * @return string
     */
    public function getCategoryId();

    /**
     * @param $value
     * @return TicketInterface
     */
    public function setCategoryId($value);

    /**
     * @param $value
     * @return TicketInterface
     */
    public function setCode($value);

    /**
     * @return string
     */
    public function getCode();

    /**
     * @param $value
     * @return TicketInterface
     */
    public function setContent($value);

    /**
     * @return string
     */
    public function getContent();

    /**
     * @param $value
     * @return TicketInterface
     */
    public function setCustomerEmail($value);

    /**
     * @return string
     */
    public function getCustomerEmail();

    /**
     * @param $value
     * @return TicketInterface
     */
    public function setCustomerName($value);

    /**
     * @return string
     */
    public function getCustomerName();


    /**
     * @param $value
     * @return TicketInterface
     */
    public function setOrder($value);

    /**
     * @return string
     */
    public function getOrder();


    /**
     * @param $value
     * @return TicketInterface
     */
    public function setPhone($value);

    /**
     * @return string
     */
    public function getPhone();

    /**
     * @param $value
     * @return TicketInterface
     */
    public function setPriority($value);

    /**
     * @return string
     */
    public function getPriority();

    /**
     * @param $value
     * @return TicketInterface
     */
    public function setStatus($value);

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @param $value
     * @return TicketInterface
     */
    public function setLastReplyName($value);

    /**
     * @return string
     */
    public function getLastReplyName();

    /**
     * @param $value
     * @return TicketInterface
     */
    public function setLastReplyDate($value);

    /**
     * @return string
     */
    public function getLastReplyDate();

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Branch8\HelpDesk\Api\Data\TicketExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Branch8\HelpDesk\Api\Data\TicketExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Branch8\HelpDesk\Api\Data\TicketExtensionInterface $extensionAttributes);
}
