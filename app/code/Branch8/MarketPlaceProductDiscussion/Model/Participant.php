<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       03/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model;

use Branch8\MarketPlaceProductDiscussion\Api\Data\ParticipantInterface;
use Magento\Framework\Model\AbstractModel;

/**
 *
 */
class Participant extends AbstractModel implements ParticipantInterface
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(
            \Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Participant::class
        );
    }

    /**
     * @param int $threadId
     * @return Participant
     */
    public function setThreadId(int $threadId)
    {
        return $this->setData('thread_id', $threadId);
    }

    /**
     * @return void
     */
    public function getThreadId()
    {
        return $this->getData('thread_id');
    }

    /**
     * @param string $userType
     * @return ParticipantInterface|Participant
     */
    public function setUserType(string $userType)
    {
        return $this->setData('user_type', $userType);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getUserType()
    {
        return $this->getData('user_type');
    }

    /**
     * @param int $userId
     * @return ParticipantInterface|Participant
     */
    public function setUserId(int $userId)
    {
        return $this->setData('user_id', $userId);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getUserId()
    {
        return $this->getData('user_id');
    }

    /**
     * @param bool $isOwner
     * @return ParticipantInterface|Participant
     */
    public function setIsOwner(bool $isOwner)
    {
        return $this->setData('is_owner', $isOwner);
    }

    /**
     * @return void
     */
    public function getIsOwner()
    {
        return $this->getData('is_owner');
    }

    /**
     * @param bool $isSeller
     * @return ParticipantInterface|Participant
     */
    public function setIsSeller(bool $isSeller)
    {
        return $this->setData('is_seller', $isSeller);
    }

    /**
     * @return void
     */
    public function getIsSeller()
    {
        return $this->getData('is_seller');
    }
}
