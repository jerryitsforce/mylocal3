<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Ticket\Create;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Branch8\HelpDesk\Controller\Adminhtml\Ticket\Create as BaseController;
use Magento\Framework\Controller\ResultFactory;

class Index extends BaseController implements HttpGetActionInterface
{
    /**
     * Index page
     *
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page|(\Magento\Framework\View\Result\Page&\Magento\Framework\Controller\ResultInterface)
     */
    public function execute()
    {
        $this->initTicket();
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Branch8_HelpDesk::helpdesk');
        $resultPage->getConfig()->getTitle()->prepend(__('Tickets'));
        $resultPage->getConfig()->getTitle()->prepend(__('New Ticket'));
        return $resultPage;
    }
}
