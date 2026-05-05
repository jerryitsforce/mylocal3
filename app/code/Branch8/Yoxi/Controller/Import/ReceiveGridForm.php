<?php

namespace Branch8\Yoxi\Controller\Import;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Branch8\Yoxi\Helper\Import as ImportHelper;

class ReceiveGridForm extends Action implements HttpPostActionInterface
{
    /** @var ResultFactory */
    protected $resultFactory;

    /** @var ImportHelper */
    protected $importHelper;

    public function __construct(
        Context       $context,
        ResultFactory $resultFactory,
        ImportHelper  $importHelper
    ) {
        parent::__construct($context);
        $this->resultFactory = $resultFactory;
        $this->importHelper = $importHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->importHelper->handle();

        return $this->redirectToPreviousPage();
    }

    /**
     * 導回先前頁面
     *
     * @return Redirect
     */
    protected function redirectToPreviousPage(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}
