<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model;

use Branch8\MarketPlaceProductDiscussion\Api\DiscussionManagementInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Exception\LocalizedException;
use Branch8\MarketPlaceProductDiscussion\Model\ThreadFactory;
use Branch8\MarketPlaceProductDiscussion\Model\MessageFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 *
 */
class DiscussionManagement implements DiscussionManagementInterface
{
    private $threadRepository;
    private $messageRepository;
    private $threadFactory;
    private $messageFactory;

    private CustomerRepository $customerRepository;
    private \Magento\Framework\Event\ManagerInterface $eventManager;
    private StoreManagerInterface $storeManager;
    private DateTime $dateTime;

    /**
     * @param CustomerRepository $customerRepository
     * @param ThreadRepository $threadRepository
     * @param MessageRepository $messageRepository
     * @param \Branch8\MarketPlaceProductDiscussion\Model\ThreadFactory $threadFactory
     * @param \Branch8\MarketPlaceProductDiscussion\Model\MessageFactory $messageFactory
     * @param StoreManagerInterface $storeManager
     * @param DateTime $dateTime
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        CustomerRepository                        $customerRepository,
        ThreadRepository                          $threadRepository,
        MessageRepository                         $messageRepository,
        ThreadFactory                             $threadFactory,
        MessageFactory                            $messageFactory,
        StoreManagerInterface                     $storeManager,
        DateTime                                  $dateTime,
        \Magento\Framework\Event\ManagerInterface $eventManager
    )
    {
        $this->eventManager = $eventManager;
        $this->threadRepository = $threadRepository;
        $this->messageRepository = $messageRepository;
        $this->threadFactory = $threadFactory;
        $this->messageFactory = $messageFactory;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->dateTime = $dateTime;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Customer\Api\Data\CustomerInterface $seller
     * @param int $customerId
     * @param string $message
     * @param string $threadType
     * @param array $meta
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function createThread(
        \Magento\Catalog\Model\Product               $product,
        \Magento\Customer\Api\Data\CustomerInterface $seller,
        int                                          $customerId,
        string                                       $message,
        string                                       $threadType = 'question',
        array                                        $meta = []
    )
    {
        $customer = $this->getCustomer((int)$customerId);
        $thread = $this->threadFactory->create();
        $thread->setProductId((int)$product->getId());
        $thread->setSku($product->getSku());
        $thread->setProductSpec($product->getSpecification());
        $thread->setProductName($product->getName());
        $thread->setSellerId($seller->getId());
        $thread->setSellerName($seller->getFirstname() . ' ' . $seller->getLastname());
        $thread->setAuthorName($customer ?
            sprintf('%s %s', $customer->getFirstname(), $customer->getLastname()) : '', '');
        $thread->setThreadType($threadType);
        $thread->setContent($message);

        $thread->setAuthorId($customerId);
        $thread->setAuthorType(Thread::THREAD_AUTHOR_TYPE_CUSTOMER);
        $thread->setStoreId($this->storeManager->getStore()->getStoreId());
        $thread->setStatus('pending');
        $thread->setMeta(json_encode($meta));
        $this->threadRepository->save($thread);
        return $thread;
    }

    /**
     * @param int $customerId
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     * @throws LocalizedException
     */
    private function getCustomer(int $customerId)
    {
        try {
            return $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /***
     * @param $threadId
     * @param $authorType
     * @param $authorId
     * @param $authorName
     * @param $message
     * @param $parentMessageId
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function replyToThread(
        $threadId,
        $authorType,
        $authorId,
        $authorName,
        $message,
        $parentMessageId = null
    )
    {
        $thread = $this->threadRepository->getById($threadId);
        if ($thread->getIsLocked()) {
            throw new LocalizedException(__('Thread is locked.'));
        }
        $reply = $this->messageFactory->create();
        $reply->setThreadId($threadId);
        $reply->setAuthorType($authorType);
        $reply->setAuthorName($authorName);
        $reply->setAuthorId($authorId);
        $reply->setMessage($message);
        $reply->setParentMessageId($parentMessageId);
        if ($authorType === 'seller') {
            $reply->setIsSellerReply(true);
            $thread->setIsSellerReply(true);
        }
        if ((int)$thread->getMessageCount() === 0) {
            $thread->setSellerRepliedAt($this->dateTime->gmtDate('Y-m-d H:i:s'));
        }
        $reply = $this->messageRepository->save($reply);
        $thread->setMessageCount($thread->getMessageCount() + 1);
        $this->threadRepository->save($thread);
        return $this->messageRepository->getById($reply->getId());
    }

    /**
     * @param $messageId
     * @param $userType
     * @param $userId
     * @return true
     */
    public function voteMessage($messageId, $userType, $userId)
    {
        // Vote logic (prevent duplicate handled via unique DB constraint)
        return true;
    }

    /**
     * @param $threadId
     * @return \Branch8\MarketPlaceProductDiscussion\Api\Data\ThreadInterface|mixed
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function approveThread($threadId)
    {
        $thread = $this->threadRepository->getById($threadId);
        $thread->setStatus('approved');
        return $this->threadRepository->save($thread);
    }

    /**
     * @param $threadId
     * @return \Branch8\MarketPlaceProductDiscussion\Api\Data\ThreadInterface|mixed
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function lockThread($threadId)
    {
        $thread = $this->threadRepository->getById($threadId);
        $thread->setIsLocked(1);
        return $this->threadRepository->save($thread);
    }
}
