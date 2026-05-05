<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Ticket;

use Branch8\HelpDesk\Model\Ticket\AclRole;
use Branch8\HelpDesk\Model\TicketFactory;
use Magento\Backend\App\Action\Context;
use Branch8\HelpDesk\Controller\Adminhtml\Ticket\Create\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\View\Result\PageFactory;

abstract class Create extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = AclRole::NEW_TICKET;
    private $ticketFactory;
    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    protected $registry;

    protected $session;

    /**
     * @param TicketFactory $ticketFactory
     * @param Context $context
     * @param \Magento\Framework\Escaper $escaper
     * @param PageFactory $resultPageFactory
     * @param ForwardFactory $resultForwardFactory
     * @param \Magento\Backend\Model\Auth\Session $session
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        TicketFactory                       $ticketFactory,
        Context                             $context,
        \Magento\Framework\Escaper          $escaper,
        PageFactory                         $resultPageFactory,
        ForwardFactory                      $resultForwardFactory,
        \Magento\Backend\Model\Auth\Session $session,
        \Magento\Framework\Registry         $registry
    )
    {
        parent::__construct($context);
        $this->session = $session;
        $this->escaper = $escaper;
        $this->registry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->ticketFactory = $ticketFactory;
    }

    /**
     * @return \Branch8\HelpDesk\Model\Ticket
     */
    protected function initTicket()
    {
        $ticket = $this->ticketFactory->create();
        $ticket->addData($this->_request->getPostValue())->setId(null);
        return $ticket;
    }
}

