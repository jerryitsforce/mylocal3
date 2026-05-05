<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Ticket;

use Branch8\HelpDesk\Api\TicketRepositoryInterface;
use Branch8\HelpDesk\Controller\AbstractController;
use Branch8\HelpDesk\Model\TicketFactory;
use Branch8\HelpDesk\Model\Ticket\Authorize;
use Branch8\HelpDesk\Model\Ticket;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * View Controller
 */
class View extends AbstractController
{
    /**
     * @var Authorize
     */
    private $authorize;
    /**
     * @var TicketFactory
     */
    private $ticketRepository;

    /**
     * @param Authorize $authorize
     * @param TicketRepositoryInterface $ticketRepository
     * @param Context $context
     */
    public function __construct(
        Authorize                 $authorize,
        TicketRepositoryInterface $ticketRepository,
        Context                   $context
    )
    {
        $this->ticketRepository = $ticketRepository;
        $this->authorize = $authorize;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $ticket = $this->getTicket();
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('helpdesk/ticket/');
        if (!$ticket || !$ticket->getId() || !$this->authorize->authorize($ticket)) {
            $this->messageManager->addErrorMessage(
                __('You do not have permission to view this page.')
            );
            return $resultRedirect;
        }
        $resultPage = $this->resultFactory->create(
            ResultFactory::TYPE_PAGE
        );
        $this->_view->loadLayout();
        $resultPage->getConfig()->getTitle()->set($ticket->getTitle());
        $this->_view->renderLayout();
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
