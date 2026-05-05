<?php

declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Ticket;

use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Branch8\HelpDesk\Model\EmailNotificationInterface;
use Branch8\HelpDesk\Model\Ticket;
use Branch8\HelpDesk\Model\TicketFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Save Ticket
 */
class Save extends \Branch8\HelpDesk\Controller\Adminhtml\Ticket
{
    /**
     * @var TicketFactory
     */
    private $ticketFactory;
    /**
     * @var TicketRepositoryInterface
     */
    private $ticketRepository;
    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;
    /**
     * @var Ticket\AssignTicketToUser
     */
    private Ticket\AssignTicketToUser $assignTicketToUser;
    /**
     * @var Ticket\Status
     */
    private Ticket\Status $status;
    /**
     * @var EmailNotificationInterface
     */
    private EmailNotificationInterface $emailNotification;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param TicketFactory $ticketFactory
     * @param DataPersistorInterface $dataPersistor
     * @param TicketRepositoryInterface $ticketRepository
     * @param Ticket\AssignTicketToUser $assignTicketToUser
     * @param Ticket\Status $status
     * @param EmailNotificationInterface $emailNotification
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry         $coreRegistry,
        TicketFactory                       $ticketFactory,
        DataPersistorInterface              $dataPersistor,
        TicketRepositoryInterface           $ticketRepository,
        Ticket\AssignTicketToUser           $assignTicketToUser,
        Ticket\Status                       $status,
        EmailNotificationInterface          $emailNotification
    )
    {
        parent::__construct($context, $coreRegistry);
        $this->assignTicketToUser = $assignTicketToUser;
        $this->ticketRepository = $ticketRepository;
        $this->ticketFactory = $ticketFactory;
        $this->dataPersistor = $dataPersistor;
        $this->status = $status;
        $this->emailNotification = $emailNotification;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            if (empty($data['ticket_id'])) {
                $data['ticket_id'] = null;
            }
            /** @var Ticket $ticket */
            $ticket = $this->ticketFactory->create();
            $id = $this->getRequest()->getParam('ticket_id');
            if ($id) {
                try {
                    $ticket = $this->ticketRepository->getById((int)$id);
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(
                        __('This ticket no longer exists.')
                    );
                    return $resultRedirect->setPath('*/*/');
                }
            }
            $oldStatus = (int)$ticket->getData('status');
            $oldUserId = (int)$ticket->getUserId();
            $newUserId = (int)$data['user_id'];
            $needNotifyUser = $oldUserId !== $newUserId;
            $ticket->setData($data);
            try {
                if (isset($data['user_id']) && $data['user_id']) {
                    $this->assignTicketToUser->assign($ticket, (int)$data['user_id']);
                }
                $ticket = $this->ticketRepository->save($ticket);
                $newStatus = (int)$ticket->getData('status');
                $needNotifyCustomer = $this->status->isStatusNeedToNotify($newStatus) &&
                    ($oldStatus !== $newStatus);
                $this->messageManager->addSuccessMessage(__('You saved the ticket.'));
                $this->dataPersistor->clear('helpdesk_ticket');
                if ($needNotifyCustomer) {
                    $this->notifyCustomner($ticket);
                }
                if ($needNotifyUser && $ticket->getUserId()) {
                    $this->notifyUser($ticket);
                }
                return $this->processBlockReturn($ticket, $data, $resultRedirect);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the ticket.'));
            }
            $this->dataPersistor->set('helpdesk_ticket', $data);
            return $resultRedirect->setPath('*/*/edit', ['ticket_id' => $id]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param $model
     * @param $data
     * @param $resultRedirect
     * @return mixed
     */
    private function processBlockReturn($model, $data, $resultRedirect)
    {
        $redirect = $this->getRequest()->getParam('back') == 'edit' ? 'continue' : 'close';
        if ($redirect === 'continue') {
            $resultRedirect->setPath('*/*/edit', ['ticket_id' => $model->getId()]);
        } elseif ($redirect === 'close') {
            $resultRedirect->setPath('*/*/');
        }
        return $resultRedirect;
    }

    /**
     * @param Ticket $ticket
     * @return mixed
     */
    private function notifyCustomner(Ticket $ticket)
    {
        return $this->emailNotification->notifyCustomerWhenStatusChange(
            $ticket, $ticket->getCustomer()
        );
    }

    /**
     * @param Ticket $ticket
     * @return mixed
     */
    private function notifyUser(Ticket $ticket)
    {
        return $this->emailNotification->notifyUserWhenAssignedTicket(
            $ticket,
            $ticket->getUser()->getEmail()
        );
    }
}
