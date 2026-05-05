<?php

declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Ticket;

use Branch8\HelpDesk\Controller\AbstractController;
use Magento\Framework\Controller\ResultFactory;

/**
 * Index controller
 */
class Index extends AbstractController
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page|(\Magento\Framework\View\Result\Page&\Magento\Framework\Controller\ResultInterface)
     */
    public function execute()
    {
        $this->resultFactory->create(
            ResultFactory::TYPE_PAGE
        );
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
