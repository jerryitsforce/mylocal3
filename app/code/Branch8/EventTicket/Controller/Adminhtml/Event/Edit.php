<?php

namespace Branch8\EventTicket\Controller\Adminhtml\Event;

class Edit extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $resultPageFactory;
    /**
     * @var \Branch8\Customer\Model\TicketEventFactory
     */
    protected $ticketEventFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Branch8\Customer\Model\TicketEventFactory $ticketEventFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Branch8\EventTicket\Model\TicketEventFactory $ticketEventFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->ticketEventFactory = $ticketEventFactory;
        parent::__construct($context);
    }
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute(){
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magento_Backend::marketing');
        $id = $this->getRequest()->getParam('id');
        $org = $this->ticketEventFactory->create()->load((int)$id);
        if(!$org->getId()){
            $resultPage->getConfig()->getTitle()->set(__('Create New Pool'));
        }
        return $resultPage;
    }

    /**
     * @return mixed
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_EventTicket::event_ticket');
    }
}