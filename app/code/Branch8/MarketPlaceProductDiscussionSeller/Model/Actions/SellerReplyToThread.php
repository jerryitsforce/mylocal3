<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       15/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussionSeller\Model\Actions;

use Branch8\MarketPlaceProductDiscussion\Model\DiscussionManagement;
use Branch8\MarketPlaceProductDiscussion\Model\Message;
use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Branch8\MarketPlaceProductDiscussionCustomer\Model\SellerReplyDiscussionNotification;
use Branch8\MarketPlaceProductDiscussionSeller\Model\Email\Sender;
use Magenest\NotificationBox\Model\CustomerNotification as CustomerNotificationModel;
use Magenest\NotificationBox\Model\Notification;
use Magenest\NotificationBox\Model\ResourceModel\NotificationQueue;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Magenest\NotificationBox\Model\ResourceModel\Notification\CollectionFactory as NotificationCollectionFactory;
use Magenest\NotificationBox\Model\ResourceModel\NotificationQueue as NotificationResource;
use Magenest\NotificationBox\Model\NotificationQueueFactory as NotificationQueueFactory;
use Magenest\NotificationBox\Model\CustomerNotificationFactory;

class SellerReplyToThread
{
    private DiscussionManagement $discussionManagement;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var NotificationCollectionFactory
     */
    private NotificationCollectionFactory $collectionFactory;
    /**
     * @var NotificationResource
     */
    private NotificationQueue $notificationQueue;

    private \Magenest\NotificationBox\Model\ResourceModel\CustomerNotification $customerNotificationResource;
    /**
     * @var NotificationResource
     */
    private NotificationResource $notificationQueueResource;
    /**
     * @var NotificationQueueFactory
     */
    private NotificationQueueFactory $notificationQueueFactory;
    /**
     * @var CustomerNotificationFactory
     */
    private CustomerNotificationFactory $customerNotificationFactory;
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;
    /**
     * @var UrlInterface
     */
    private UrlInterface $urlInterface;
    /**
     * @var Sender
     */
    private Sender $sender;
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @param NotificationCollectionFactory $collectionFactory
     * @param DiscussionManagement $discussionManagement
     * @param LoggerInterface $logger
     * @param NotificationResource $notificationQueueResource
     * @param NotificationQueueFactory $notificationQueueFactory
     * @param CustomerNotificationFactory $customerNotificationFactory
     * @param ProductRepositoryInterface $productRepository
     * @param \Magenest\NotificationBox\Model\ResourceModel\CustomerNotification $customerNotificationResource
     * @param CustomerRepositoryInterface $customerRepository
     * @param Sender $sender
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        NotificationCollectionFactory                                      $collectionFactory,
        DiscussionManagement                                               $discussionManagement,
        LoggerInterface                                                    $logger,
        NotificationResource                                               $notificationQueueResource,
        NotificationQueueFactory                                           $notificationQueueFactory,
        CustomerNotificationFactory                                        $customerNotificationFactory,
        ProductRepositoryInterface                                         $productRepository,
        \Magenest\NotificationBox\Model\ResourceModel\CustomerNotification $customerNotificationResource,
        CustomerRepositoryInterface                                        $customerRepository,
        Sender                                                             $sender,
        UrlInterface                                                       $urlInterface
    )
    {
        $this->customerNotificationResource = $customerNotificationResource;
        $this->notificationQueueFactory = $notificationQueueFactory;
        $this->notificationQueueResource = $notificationQueueResource;
        $this->logger = $logger;
        $this->discussionManagement = $discussionManagement;
        $this->customerNotificationFactory = $customerNotificationFactory;
        $this->productRepository = $productRepository;
        $this->collectionFactory = $collectionFactory;
        $this->urlInterface = $urlInterface;
        $this->customerRepository = $customerRepository;
        $this->sender = $sender;
    }

    /**
     * @param Thread $thread
     * @param $sellerId
     * @param $sellerName
     * @param $message
     * @return mixed
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function reply(Thread $thread, $sellerId,$sellerName, $message)
    {
        $reply = $this->discussionManagement->replyToThread(
            $thread->getId(),
            Message::AUTHOR_TYPE_SELLER,
            $sellerId,
            $sellerName,
            $message
        );
        $this->notifyToCustomerMessageBox($thread, $reply, $thread->getAuthorId());
        $this->notifyToCustomerEmail($thread, $reply, $thread->getAuthorId());
        return $reply;
    }

    /**
     * @param Thread $thread
     * @param Message $message
     * @param $customerId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function notifyToCustomerEmail(Thread $thread, Message $message, $customerId)
    {
         $this->sender->send($thread, $message, $this->customerRepository->getById($customerId));
    }

    /**
     * @param Thread $thread
     * @param Message $message
     * @param $customerId
     * @return void
     */
    private function notifyToCustomerMessageBox(Thread $thread, Message $message, $customerId)
    {
        try {
            $title = __('The merchant has replied to your message');
            $content = $message->getMessage();
            $listNotification = $this->getListNotifications();
            $product = $this->productRepository->getById((int)$thread->getProductId());
            $url = $product->getProductUrl() . '#' . 'discussions';
            /**
             * @var $notification \Magenest\NotificationBox\Model\Notification
             */
            foreach ($listNotification as $notification) {
                //$sendTime = $notification->getSendTime();
                /**
                 * @TODO  Not check schedule send - need to use message queue
                 */
                $data = [
                    'customer_id' => $customerId,
                    'notification_type' => $notification->getNotificationType(),
                    'description' => $title,
                    'notification_id' => $notification->getId(),
                    //'created_at' => $message->getCreatedAt(),
                    'detail_url' => $url,
                    'redirect_url' => $url,
                    'icon' => $notification['image'],
                    'star' => CustomerNotificationModel::UNSTAR,
                    'status' => CustomerNotificationModel::STATUS_UNREAD,
                    'detail_description' => $content
                ];
                $customerNotification = $this->customerNotificationFactory->create();
                $customerNotification->addData($data);
                $this->customerNotificationResource->save($customerNotification);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->logger->info($e->getTraceAsString());
        }
    }

    /**
     * @return mixed
     */
    private function getListNotifications()
    {
        return $this->collectionFactory->create()
            ->addFieldToFilter('is_active', Notification::ACTIVE)
            ->addFieldToFilter('notification_type', SellerReplyDiscussionNotification::CODE);
    }
}
