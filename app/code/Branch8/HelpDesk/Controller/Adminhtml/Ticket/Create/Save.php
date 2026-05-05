<?php

declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Ticket\Create;

use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Branch8\HelpDesk\Model\EmailNotification;
use Branch8\HelpDesk\Model\EmailNotificationInterface;
use Branch8\HelpDesk\Model\Ticket;
use Branch8\HelpDesk\Model\Ticket\CreateMessageFromPostAction;
use Branch8\HelpDesk\Model\Ticket\LastReplyAction;
use Branch8\HelpDesk\Model\TicketFactory;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;

class Save extends \Branch8\HelpDesk\Controller\Adminhtml\Ticket\Create implements HttpPostActionInterface
{
    private TicketRepositoryInterface $ticketRepository;

    private $emailNotification;
    private UserFactory $userFactory;
    private $validate;
    private Ticket\Status $status;
    private CreateMessageFromPostAction $createMessgeFromPostAction;
    private LastReplyAction $lastReplyAction;

    /**
     * @param TicketFactory $ticketFactory
     * @param TicketRepositoryInterface $ticketRepository
     * @param Context $context
     * @param EmailNotificationInterface $emailNotification
     * @param UserFactory $userFactory
     * @param \Magento\Framework\Escaper $escaper
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param \Magento\Backend\Model\Auth\Session $session
     * @param Ticket\Validate $validate
     * @param Ticket\Status $status
     * @param \Magento\Framework\Registry $registry
     * @param CreateMessageFromPostAction $createMessageFromPostAction
     * @param LastReplyAction $lastReplyAction
     */
    public function __construct(
        TicketFactory                         $ticketFactory,
        TicketRepositoryInterface             $ticketRepository,
        Context                               $context,
        EmailNotificationInterface            $emailNotification,
        UserFactory                           $userFactory,
        \Magento\Framework\Escaper            $escaper,
        PageFactory                           $resultPageFactory,
        ForwardFactory                        $resultForwardFactory,
        \Magento\Backend\Model\Auth\Session   $session,
        Ticket\Validate                       $validate,
        \Branch8\HelpDesk\Model\Ticket\Status $status,
        \Magento\Framework\Registry           $registry,
        CreateMessageFromPostAction           $createMessageFromPostAction,
        LastReplyAction                       $lastReplyAction
    )
    {
        parent::__construct(
            $ticketFactory,
            $context,
            $escaper,
            $resultPageFactory,
            $resultForwardFactory,
            $session,
            $registry
        );
        $this->ticketRepository = $ticketRepository;
        $this->emailNotification = $emailNotification;
        $this->userFactory = $userFactory;
        $this->validate = $validate;
        $this->status = $status;
        $this->createMessgeFromPostAction = $createMessageFromPostAction;
        $this->lastReplyAction = $lastReplyAction;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        /**
         * @var $ticket Ticket
         * @Var $user User
         */
        $ticket = $this->initTicket();
        $userId = (int)$ticket->getUserId();
        $ticket->setCreateBy($this->session->getUser()->getId());
        $user = $this->userFactory->create()->load($userId);
        if (!$ticket->getUserId()) {
            $ticket->setData('user_id', null);
        }
        try {
            $shouldNotifyToUser = $userId && ($userId !== (int)$this->session->getUser()->getId());
            $notifyCustomer = (bool)$this->getRequest()->getParam('notify_customer');
            $ticket = $this->ticketRepository->save($ticket);
            $this->messageManager->addSuccessMessage(__('You created new ticket.'));
            $message = $this->createMessgeFromPostAction->createForUser(
                $ticket,
                $user,
                $this->getRequest()->getParams()
            );
            $this->lastReplyAction->saveByUser($ticket, $user);
            /**
             * save first message attachments for ticket
             */
            if ($attachments = $message->getExtensionAttributes()->getAttachments()) {
                $attachmentIds = [];
                foreach ($attachments as $item) {
                    $attachmentIds[] = $item->getId();
                }
                if ($attachmentIds) {
                    $ticket->setData('attachment_ids', join(',', $attachmentIds))->save();
                }
            }
            if ($shouldNotifyToUser && $user->getId()) {
                $this->emailNotification->notifyUserWhenAssignedTicket(
                    $ticket,
                    $user->getEmail(),
                    $attachments
                );
            }
            if ($notifyCustomer) {
                $this->emailNotification->notifyCustomerWhenHaveNewTicket($ticket, $ticket->getCustomerEmail(), $attachments);
            }

            return $resultRedirect->setPath('*/ticket/edit', ['ticket_id' => $ticket->getId()]);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the ticket.'));
        }
        return $resultRedirect->setPath('*/*/');
    }

}
