<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Api\Data;


use Magento\Framework\DataObject;

interface MessageDataInterface extends \Webkul\MpBuyerSellerChat\Api\Data\MessageInterface
{
    public const MESSAGE_TYPE = 'message_type';

    public const META = 'meta';
    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get last product ID
     *
     * @return int|null
     */
    public function getProductId();

    /**
     * Get sender ID
     *
     * @return string|null
     */
    public function getSenderUniqueId();

    /**
     * Get receiver ID
     *
     * @return string|null
     */
    public function getReceiverUniqueId();

    /**
     * Get Message
     *
     * @return string|null
     */
    public function getMessage();

    /**
     * Get Message
     *
     * @return string|null
     */
    public function getDate();

    /**
     * Get SenderName
     *
     * @return string|null
     */
    public function getSenderName();

    /**
     * Get ReceiverName
     *
     * @return string|null
     */
    public function getReceiverName();

    /**
     * Get Message Type
     *
     * @return string|null
     */
    public function getMesssageType();

    /**
     * Set ID
     *
     * @param int $id
     * @return MessageDataInterface
     */
    public function setId($id);

    /**
     * Set last product ID
     *
     * @param int $productId
     * @return MessageDataInterface
     */
    public function setProductId($productId);

    /**
     * Set sender id
     *
     * @param string $senderUniqueId
     * @return \Webkul\MpBuyerSellerChat\Api\Data\MessageInterface
     */
    public function setSenderUniqueId($senderUniqueId);

    /**
     * Set receiver unique id
     *
     * @param string $receiverUniqueId
     * @return MessageDataInterface
     */
    public function setReceiverUniqueId($receiverUniqueId);

    /**
     * Set receiver id
     *
     * @param string $message
     * @return MessageDataInterface
     */
    public function setMessage($message);

    /**
     * Set date
     *
     * @param string $date
     * @return MessageDataInterface
     */
    public function setDate($date);

    /**
     * Set SenderName
     *
     * @param string $senderName
     * @return MessageDataInterface
     */
    public function setSenderName($senderName);

}
