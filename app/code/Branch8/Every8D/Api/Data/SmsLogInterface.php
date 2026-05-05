<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Every8D\Api\Data;

interface SmsLogInterface
{

    const UPDATED_AT = 'updated_at';
    const CONTENT = 'content';
    const CREATED_AT = 'created_at';
    const RESPONSE = 'response';
    const IP = 'ip';
    const SMSLOG_ID = 'smslog_id';
    const PHONE = 'phone';

    /**
     * Get smslog_id
     * @return string|null
     */
    public function getSmslogId();

    /**
     * Set smslog_id
     * @param string $smslogId
     * @return \Branch8\Every8D\SmsLog\Api\Data\SmsLogInterface
     */
    public function setSmslogId($smslogId);

    /**
     * Get phone
     * @return string|null
     */
    public function getPhone();

    /**
     * Set phone
     * @param string $phone
     * @return \Branch8\Every8D\SmsLog\Api\Data\SmsLogInterface
     */
    public function setPhone($phone);

    /**
     * Get content
     * @return string|null
     */
    public function getContent();

    /**
     * Set content
     * @param string $content
     * @return \Branch8\Every8D\SmsLog\Api\Data\SmsLogInterface
     */
    public function setContent($content);

    /**
     * Get response
     * @return string|null
     */
    public function getResponse();

    /**
     * Set response
     * @param string $response
     * @return \Branch8\Every8D\SmsLog\Api\Data\SmsLogInterface
     */
    public function setResponse($response);

    /**
     * Get ip
     * @return string|null
     */
    public function getIp();

    /**
     * Set ip
     * @param string $ip
     * @return \Branch8\Every8D\SmsLog\Api\Data\SmsLogInterface
     */
    public function setIp($ip);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Branch8\Every8D\SmsLog\Api\Data\SmsLogInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \Branch8\Every8D\SmsLog\Api\Data\SmsLogInterface
     */
    public function setUpdatedAt($updatedAt);
}

