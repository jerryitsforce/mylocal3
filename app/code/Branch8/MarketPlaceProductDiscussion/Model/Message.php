<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model;

use Magento\Framework\Model\AbstractModel;
use Branch8\MarketPlaceProductDiscussion\Api\Data\MessageInterface;

/**
 * Message model
 * @api
 * @method MessageInterface setThreadId(int $threadId)
 * @method MessageInterface setAuthorType(string $authorType)
 * @method MessageInterface setAuthorId(int $authorId)
 * @method MessageInterface setMessage(string $message)
 * @method MessageInterface isEdited(bool $isEdited)
 * @method MessageInterface setIsSellerReply(bool $isSellerReply)
 * @method MessageInterface setIsModerated(bool $isModerated)
 * @method MessageInterface setHelpfullCount(int $helpfullCount)
 * @method int getHelpfullCount()
 * @method int getThreadId()
 * @method string getAuthorType()
 * @method int getAuthorId()
 * @method string getMessage()
 * @method bool getIsEdited()
 * @method bool getIsSellerReply()
 * @method bool getIsModerated()
 */
class Message extends AbstractModel implements MessageInterface
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(
            \Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Message::class
        );
    }
}
