<?php

namespace Branch8\HotaiCore\Controller\Barcode;

use Branch8\HotaiCore\Helper\Barcode as BarcodeHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class Display extends Action
{
    /** @var RequestInterface */
    protected $request;

    /** @var BarcodeHelper */
    protected $barcodeHelper;

    /** @var ResultFactory */
    protected $resultFactory;

    /** @var MessageManagerInterface */
    protected $messageManager;

    public function __construct(
        RequestInterface $request,
        BarcodeHelper $barcodeHelper,
        ResultFactory $resultFactory,
        MessageManagerInterface $messageManager,
        \Magento\Framework\App\Action\Context $context,
    ) {
        $this->request        = $request;
        $this->barcodeHelper  = $barcodeHelper;
        $this->resultFactory  = $resultFactory;
        $this->messageManager = $messageManager;

        return parent::__construct($context);
    }

    public function execute()
    {
        try {
            $barcodeType    = $this->request->getParam('barcode_type');
            $barcodeContent = $this->request->getParam('barcode_content');

            if (empty($barcodeType) || empty($barcodeContent)) {
                throw new \Exception(__("BarcodeType or barcodeContent is empty."));
            }

            $this->barcodeHelper->display($barcodeType, $barcodeContent);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->redirectToPreviousPage();
        }
    }

    /**
     * 導回先前頁面
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function redirectToPreviousPage(): \Magento\Framework\Controller\Result\Redirect
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}
