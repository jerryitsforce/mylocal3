<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Ticket;

use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Branch8\HelpDesk\Controller\AjaxGetMessages;
use Branch8\HelpDesk\Model\EmailNotificationInterface;
use Branch8\HelpDesk\Model\Ticket\CreateMessageFromPostAction;
use Branch8\HelpDesk\Model\Ticket\LastReplyAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Ajax Submit Ticket Message
 */
class PostMessage extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * @var TicketRepositoryInterface
     */
    private $ticketRepository;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    private $resultRawFactory;
    /**
     * @var CreateMessageFromPostAction
     */
    private $createMessageFromPostAction;
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $session;
    /**
     * @var LastReplyAction
     */
    private $lastReplyAction;
    /**
     * @var EmailNotificationInterface
     */
    private EmailNotificationInterface $emailNotification;

    /**
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param TicketRepositoryInterface $ticketRepository
     * @param CreateMessageFromPostAction $createMessageFromPostAction
     * @param \Magento\Backend\Model\Auth\Session $session
     * @param LastReplyAction $lastReplyAction
     * @param EmailNotificationInterface $emailNotification
     */
    public function __construct(
        Context                                          $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory  $resultRawFactory,
        TicketRepositoryInterface                        $ticketRepository,
        CreateMessageFromPostAction                      $createMessageFromPostAction,
        \Magento\Backend\Model\Auth\Session              $session,
        LastReplyAction                                  $lastReplyAction,
        EmailNotificationInterface                       $emailNotification
    )
    {
        $this->lastReplyAction = $lastReplyAction;
        $this->session = $session;
        $this->createMessageFromPostAction = $createMessageFromPostAction;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->ticketRepository = $ticketRepository;
        $this->emailNotification = $emailNotification;
        parent::__construct($context);
    }

    /**
     * Execute
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $httpBadRequestCode = 400;
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        $ticket = $this->getTicket();
        if (!$this->getRequest()->isXmlHttpRequest()
            || $this->getRequest()->getMethod() !== 'POST'
            || !$ticket
            || !$ticket->getId()
        ) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        $response = [
            'errors' => false,
            'messages' => __('Message was successfully sent')->render()
        ];
        try {
            /**
             * @var $ticket \Branch8\HelpDesk\Model\Ticket
             */
            $message = $this->createMessageFromPostAction->createForUser($ticket,
                $this->session->getUser(),
                $this->getRequest()->getPost()
            );
            $this->lastReplyAction->saveByUser($ticket,
                $this->session->getUser()
            );
            if (\filter_var($this->getRequest()->getParam('notify'), FILTER_VALIDATE_BOOL)) {
                $this->emailNotification->notifyCustomerWhenHaveMessage(
                    $ticket,
                    $ticket->getCustomerEmail(),
                    $message->getExtensionAttributes()->getAttachments()
                );
            }
        } catch (LocalizedException $e) {
            $response = [
                'errors' => true,
                'messages' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $response = [
                'errors' => true,
                'messages' => __('Error when create message')->render(),
            ];
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }

    /**
     * @return \Branch8\HelpDesk\Api\Data\TicketInterface|null
     */
    private function getTicket()
    {
        try {
            return $this->ticketRepository->getById(
                (int)$this->_request->getParam('ticket_id')
            );
        } catch (NoSuchEntityException $exception) {
            return null;
        }
    }
}
