<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Api\Data;

/**
 * Chat Profile interface.
 * @api
 */
interface ChatProfileInfoInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    public const ENTITY_ID = 'entity_id';
    public const CUSTOMER_ID = 'customer_id';
    public const OBJECT_ID = 'object_id';

    public const ENTITY_TYPE = 'entity_type';
    public const UNIQUE_ID = 'unique_id';
    public const CHAT_STATUS = 'chat_status';
    public const REGISTERED_AS = 'registered_as';
    public const IMAGE = 'image';
    public const NAME = 'name';
    public const EMAIL='email';
    public const NICK_NAME='nickname';
    public const TOTAL_UNREAD_MESSAGES='total_unread_messages';    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get customer ID
     *
     * @return int|null
     */
    public function getCustomerId();

    /**
     * Get customer unique ID
     *
     * @return string|null
     */
    public function getUniqueId();

    /**
     * Get chat status
     *
     * @return int|null
     */
    public function getChatStatus();

    /**
     * Get chat status
     *
     * @return string|null
     */
    public function getImage();


    /**
     * Get chat object id
     *
     * @return int|null
     */
    public function getObjectId();
    /**
     * Get Name
     *
     * @return string|null
     */
    public function getName();
    /**
     * Get entity type
     *
     * @return string|null
     */
    public function getEntityType();

    /**
     * Get chat registration type
     *
     * @return string|null
     */
    public function getRegisteredAs();

    /**
     * Set ID
     *
     * @param int $id
     * @return \Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setId($id);

    /**
     * Set customer ID
     *
     * @param int $customerId
     * @return \Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setCustomerId($customerId);

    /**
     * Set customer ID
     *
     * @param string $uniqueId
     * @return \Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setUniqueId($uniqueId);

    /**
     * Set chat status
     *
     * @param int $status
     * @return \Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setChatStatus($status);

    /**
     * Set profile image
     *
     * @param string $image
     * @return \Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setImage($image);

    /**
     * Set chat registration type
     *
     * @param string $type
     * @return \Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setRegisteredAs($type);

    /**
     * Set chat object Id
     *
     * @param int $value
     * @return \Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setObjectId($value);


    /**
     * Set chat entity type
     *
     * @param int $value
     * @return \Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setEntityType($value);

    /**
     * Set chat entity type
     *
     * @param string $value
     * @return \Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setName($value);
    /**
     * Set chat entity type
     *
     * @param string $value
     * @return \Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setEmail($value);

    /**
     * @param $value
     * @return mixed
     */
    public function getEmail();

    /**
     * Set chat entity type
     *
     * @param string $value
     * @return \Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function setNickName($value);
    /**
     * Set chat entity type
     *
     * @return \Webkul\MpBuyerSellerChat\Api\Data\ChatProfileInfoInterface
     */
    public function getNickName();
}
