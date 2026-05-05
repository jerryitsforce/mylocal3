<?php

namespace Branch8\GeneralNotifyTicket\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Branch8\GeneralNotifyTicket\Helper\Import as ImportHelper;

class ReceiveGridForm extends Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
   /** @var ImportHelper */
   protected $importHelper;

    public function __construct(
        ImportHelper $importHelper,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->importHelper = $importHelper;

        return parent::__construct($context);
    }

    public function execute()
    {
        $this->importHelper->handle();

        return $this->redirectToPreviousPage();
    }

    /**
     * 導回先前頁面
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function redirectToPreviousPage(): \Magento\Framework\Controller\Result\Redirect
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}
