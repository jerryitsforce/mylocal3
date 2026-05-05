<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Api\Data;

interface VoteInterface
{
    public const VOTE_ID = 'vote_id';
    public const MESSAGE_ID = 'message_id';
    public const USER_TYPE = 'user_type';
    public const USER_ID = 'user_id';
    public const VOTE_TYPE = 'vote_type';
    public const CREATED_AT = 'created_at';

    public function getId();

    public function getMessageId(): int;
    public function setMessageId(int $messageId);

    public function getUserType(): string;
    public function setUserType(string $userType);

    public function getUserId(): int;
    public function setUserId(int $userId);

    public function getVoteType(): string;
    public function setVoteType(string $voteType);
}
