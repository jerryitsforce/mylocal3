<?php

declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Ticket;

use Branch8\HelpDesk\Model\Ticket\AclRole;
use Branch8\HelpDesk\Model\TicketFactory;

class Delete extends \Branch8\HelpDesk\Controller\Adminhtml\Ticket
{
    const ADMIN_RESOURCE = AclRole::REMOVE_TICKET;
    private $ticketFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param TicketFactory $ticketFactory
     */

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry         $coreRegistry,
        TicketFactory                       $ticketFactory,
    )
    {
        parent::__construct($context, $coreRegistry);
        $this->ticketFactory = $ticketFactory;
    }

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('ticket_id');
        if ($id) {
            try {
                // init model and delete
                $this->ticketFactory->create()->load($id)->delete();
                $this->messageManager->addSuccessMessage(__('You deleted the ticket.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['ticket_id' => $id]);
            }
        }
        $this->messageManager->addErrorMessage(__('We can\'t find a ticket to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}
