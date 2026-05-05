<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Controller\Adminhtml\Rma;

use Branch8\Rma\Model\Actions\PrepareRmaDataForForm;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

class GetAvaiableRmaItems extends \Magento\Backend\App\Action implements HttpPostActionInterface, HttpGetActionInterface
{
    private JsonFactory $resultJsonFactory;
    private $orderRepository;

    private $prepareRmaDataForForm;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param PrepareRmaDataForForm $prepareRmaDataForForm
     * @param OrderRepository $orderRepository
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context              $context,
        PrepareRmaDataForForm                            $prepareRmaDataForForm,
        \Magento\Sales\Model\OrderRepository             $orderRepository,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        $this->prepareRmaDataForForm = $prepareRmaDataForForm;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('orderId');
        $prepareData = $this->prepareRmaDataForForm->execute((int)$orderId);
        $response = new \Magento\Framework\DataObject();
        $response->setError(true);
        $response->setItems($prepareData['items']);
        $resultJson = $this->resultJsonFactory->create();
        if ($response->getError()) {
            $response->setError(true);
            $response->setMessages($response->getMessages());
        }
        $resultJson->setData($response);
        return $resultJson;
    }

}
