<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;

use Branch8\HelpDesk\Api\Data\TicketInterface;
use Branch8\HelpDesk\Api\Data\TicketInterfaceFactory;
use Branch8\HelpDesk\Api\MessageRepositoryInterface;
use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Branch8\HelpDesk\Model\Attachment\CreateFromPost;
use Branch8\HelpDesk\Model\Message;
use Branch8\HelpDesk\Model\Message\AssignCustomerData;
use Branch8\HelpDesk\Model\Message\AssignGuestData;
use Branch8\HelpDesk\Model\Message\AssignUserData;
use Magento\Customer\Model\Customer;
use Branch8\HelpDesk\Api\Data\MessageInterfaceFactory;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\User\Model\User;

class CreateMessageFromPostAction
{
    /**
     * @var TicketInterfaceFactory
     */
    private $ticketFactory;
    /**
     * @var TicketRepositoryInterface
     */
    private $ticketRepository;
    /**
     * @var MessageInterfaceFactory
     */
    private $messageFactory;
    /**
     * @var MessageRepositoryInterface
     */
    private $messageRepository;
    /**
     * @var Message\AssignCustomerData
     */
    private $assignCustomerData;
    /**
     * @var CreateFromPost
     */
    private $attachmentFromPost;
    /**
     * @var RemoteAddress
     */
    private $remoteAddress;
    /**
     * @var Message\AssignUserData
     */
    private $assignUserData;
    /**
     * @var AssignGuestData
     */
    protected $assignGuestData;

    /**
     * Constructor.
     *
     * @param TicketInterfaceFactory $ticketFactory
     * @param TicketRepositoryInterface $ticketRepository
     * @param MessageInterfaceFactory $messageFactory
     * @param MessageRepositoryInterface $messageRepository
     * @param AssignCustomerData $assignCustomerData
     * @param AssignUserData $assignUserData
     * @param CreateFromPost $createFromPost
     * @param RemoteAddress $remoteAddress
     * @param AssignGuestData $assignGuestData
     */
    public function __construct(
        TicketInterfaceFactory     $ticketFactory,
        TicketRepositoryInterface  $ticketRepository,
        MessageInterfaceFactory    $messageFactory,
        MessageRepositoryInterface $messageRepository,
        Message\AssignCustomerData $assignCustomerData,
        Message\AssignUserData     $assignUserData,
        CreateFromPost             $createFromPost,
        RemoteAddress              $remoteAddress,
        AssignGuestData $assignGuestData
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->attachmentFromPost = $createFromPost;
        $this->assignCustomerData = $assignCustomerData;
        $this->assignUserData = $assignUserData;
        $this->ticketRepository = $ticketRepository;
        $this->ticketFactory = $ticketFactory;
        $this->messageFactory = $messageFactory;
        $this->messageRepository = $messageRepository;
        $this->assignGuestData =$assignGuestData;
    }

    /**
     * Create first message when customer create new ticket
     * @param TicketInterface $ticket
     * @param Customer $customer
     * @param $postDetail
     * @return \Branch8\HelpDesk\Api\Data\MessageInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createForCustomer(TicketInterface $ticket, Customer $customer, $postDetail = [])
    {
        $message = $this->messageFactory->create();
        $this->assignCustomerData->assign(
            $message, $customer
        );
        $message->setContent($postDetail['content'])
            ->setBelongTo(BelongTo::CUSTOMER)->setTicketId($ticket->getId());
        $message = $this->messageRepository->save($message);
        $remoteAddress = $this->remoteAddress->getRemoteAddress();
        $this->saveAttachments($postDetail, $message);
        if ($remoteAddress) {
            $message->setRemoteAddress($remoteAddress);
        }
        $this->messageRepository->save($message);
        return $this->messageRepository->get((int)$message->getMessageId());
    }

    public function createForGuest(TicketInterface $ticket, array $customer, $postDetail = [])
    {
        $message = $this->messageFactory->create();
        $this->assignGuestData->assign(
            $message, $customer
        );
        $message->setContent($postDetail['content'])
            ->setBelongTo(BelongTo::ANOMYNOUS)->setTicketId($ticket->getId());
        $message = $this->messageRepository->save($message);
        $remoteAddress = $this->remoteAddress->getRemoteAddress();
        $this->saveAttachments($postDetail, $message);
        if ($remoteAddress) {
            $message->setRemoteAddress($remoteAddress);
        }
        $this->messageRepository->save($message);
        return $this->messageRepository->get((int)$message->getMessageId());
    }

    /**
     * Save attachments
     * @param $postDetail
     * @param Message $message
     * @return Message
     */
    private function saveAttachments($postDetail, Message $message)
    {
        if (isset($postDetail['attachment']) && is_array($postDetail['attachment'])) {
            $messageId = (int)$message->getMessageId();
            $this->attachmentFromPost->create($postDetail['attachment'], $messageId);
        }
        return $message;
    }

    /**
     * Create first message when admin create new ticket
     * @param TicketInterface $ticket
     * @param User $user
     * @param $postDetail
     * @return \Branch8\HelpDesk\Api\Data\MessageInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createForUser(TicketInterface $ticket, User $user, $postDetail = [])
    {
        $message = $this->messageFactory->create();
        $this->assignUserData->assign($message, $user);
        $message->setContent($postDetail['content'])->setBelongTo(BelongTo::USER)
            ->setTicketId($ticket->getId());
        $message = $this->messageRepository->save($message);
        $remoteAddress = $this->remoteAddress->getRemoteAddress();
        $this->saveAttachments($postDetail, $message);
        if ($remoteAddress) {
            $message->setRemoteAddress($remoteAddress);
        }
        $this->messageRepository->save($message);
        return $this->messageRepository->get((int)$message->getId());
    }
}
