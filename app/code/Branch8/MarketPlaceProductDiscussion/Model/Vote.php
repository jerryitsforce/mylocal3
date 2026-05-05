<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model;

use Branch8\MarketPlaceProductDiscussion\Api\Data\VoteInterface;
use Magento\Framework\Model\AbstractModel;
use Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Vote as ResourceVote;

/**
 *
 */
class Vote extends AbstractModel implements VoteInterface
{
    protected function _construct(): void
    {
        $this->_init(ResourceVote::class);
    }

    public function getId()
    {
        return $this->getData(self::VOTE_ID);
    }

    public function getMessageId(): int
    {
        return (int) $this->getData(self::MESSAGE_ID);
    }

    public function setMessageId(int $messageId)
    {
        return $this->setData(self::MESSAGE_ID, $messageId);
    }

    public function getUserType(): string
    {
        return (string) $this->getData(self::USER_TYPE);
    }

    public function setUserType(string $userType)
    {
        return $this->setData(self::USER_TYPE, $userType);
    }

    public function getUserId(): int
    {
        return (int) $this->getData(self::USER_ID);
    }

    public function setUserId(int $userId)
    {
        return $this->setData(self::USER_ID, $userId);
    }

    public function getVoteType(): string
    {
        return (string) $this->getData(self::VOTE_TYPE);
    }

    public function setVoteType(string $voteType)
    {
        return $this->setData(self::VOTE_TYPE, $voteType);
    }
}
