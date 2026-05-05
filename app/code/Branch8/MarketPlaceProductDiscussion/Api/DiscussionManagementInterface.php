<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Api;

interface DiscussionManagementInterface
{
    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Customer\Api\Data\CustomerInterface $seller
     * @param int $customerId
     * @param string $message
     * @param string $threadType
     * @return mixed
     */
    public function createThread(
        \Magento\Catalog\Model\Product               $product,
        \Magento\Customer\Api\Data\CustomerInterface $seller,
        int                                          $customerId,
        string                                       $message,
        string                                       $threadType = 'question'
    );

    /**
     * @param $threadId
     * @param $authorType
     * @param $authorId
     * @param $authorName
     * @param $message
     * @param $parentMessageId
     * @return mixed
     */
    public function replyToThread(
        $threadId,
        $authorType,
        $authorId,
        $authorName,
        $message,
        $parentMessageId = null
    );

    /**
     * @param $messageId
     * @param $userType
     * @param $userId
     * @return mixed
     */
    public function voteMessage(
        $messageId,
        $userType,
        $userId
    );

    /**
     * @param $threadId
     * @return mixed
     */
    public function approveThread($threadId);

    /**
     * @param $threadId
     * @return mixed
     */
    public function lockThread($threadId);
}
