<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       03/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Api\Data;

interface ParticipantInterface
{
    /**
     * @param int $threadId
     * @return ParticipantInterface
     */
    public function setThreadId(int $threadId);

    /**
     * @return int
     */
    public function getThreadId();

    /**
     * @param string $userType
     * @return ParticipantInterface
     */
    public function setUserType(string $userType);

    /**
     * @return string
     */
    public function getUserType();

    /**
     * @param int $userId
     * @return ParticipantInterface
     */
    public function setUserId(int $userId);

    /**
     * @return int
     */
    public function getUserId();

    /**
     * @param bool $isOwner
     * @return ParticipantInterface
     */
    public function setIsOwner(bool $isOwner);
    /**
     * @return bool
     */
    public function getIsOwner();

    /**
     * @param bool $isSeller
     * @return ParticipantInterface
     */
    public function setIsSeller(bool $isSeller);

    /**
     * @return bool
     */
    public function getIsSeller();
}
