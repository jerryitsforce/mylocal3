<?php
/**
 * Copyright © dev@branch8 All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HotaiPay\Api\Data;

interface MessageInterface
{

    const TYPE = 'type';
    const MESSAGE_ID = 'message_id';

    /**
     * Get message_id
     * @return string|null
     */
    public function getMessageId();

    /**
     * Set message_id
     * @param string $messageId
     * @return \Branch8\HotaiPay\Message\Api\Data\MessageInterface
     */
    public function setMessageId($messageId);

    /**
     * Get type
     * @return string|null
     */
    public function getType();

    /**
     * Set type
     * @param string $type
     * @return \Branch8\HotaiPay\Message\Api\Data\MessageInterface
     */
    public function setType($type);
}

