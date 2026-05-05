<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Controller\Adminhtml\Rma;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class ValidatePost extends \Magento\Backend\App\Action implements HttpPostActionInterface, HttpGetActionInterface
{
    private JsonFactory $resultJsonFactory;
    private ValidateHandler $postDataProcessor;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param ValidateHandler $dataProcessor
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context              $context,
        ValidateHandler                                  $dataProcessor,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->postDataProcessor = $dataProcessor;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        list($error, $messages) = $this->postDataProcessor->validate($this->getRequest()->getParams());
        $response = new \Magento\Framework\DataObject();
        $response->setError($error);
        $resultJson = $this->resultJsonFactory->create();
        if ($error) {
            $response->setMessages($messages);
        }
        $resultJson->setData($response);
        return $resultJson;
    }
}
