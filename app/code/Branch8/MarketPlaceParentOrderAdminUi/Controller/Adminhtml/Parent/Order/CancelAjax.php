<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Marketplace\Model\Actions\CancelOrderAction;
use Branch8\Marketplace\Model\Actions\GetSellerByOrder;
use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderCancel\Sender;
use Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\ParentOrder;
use Branch8\MarketPlaceParentOrderAdminUi\Model\Actions\ParentOrderCancelAction;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger as LoggerInterface;
use Magento\Backend\App\Action;
use Branch8\HotaiCore\Model\Order\Status as HotaiOrderStatus;
use Branch8\HotaiCore\Model\Order\State as HotailOrderState;

class CancelAjax extends ParentOrder implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceParentOrderAdminUi::cancel';
    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;
    private CancelOrderAction $cancelOrderAction;
    private GetSellerByOrder $getSellerByOrder;
    private ParentOrderCancelAction $parentOrderCancelAction;

    private $sender;

    /**
     * @param Action\Context $context
     * @param Registry $coreRegistry
     * @param JsonFactory $resultJsonFactory
     * @param PageFactory $resultPageFactory
     * @param LoggerInterface $logger
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param ParentOrderCancelAction $parentOrderCancelAction
     */
    public function __construct(
        Action\Context                 $context,
        Registry                       $coreRegistry,
        JsonFactory                    $resultJsonFactory,
        PageFactory                    $resultPageFactory,
        LoggerInterface                $logger,
        Sender $sender,
        ParentOrderRepositoryInterface $parentOrderRepository,
        ParentOrderManagementInterface $parentOrderManagement,
        ParentOrderCancelAction        $parentOrderCancelAction
    )
    {
        parent::__construct(
            $context,
            $coreRegistry,
            $resultPageFactory,
            $logger,
            $parentOrderRepository,
            $parentOrderManagement
        );
        $this->sender = $sender;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->parentOrderCancelAction = $parentOrderCancelAction;
    }

    /**
     * Cancel order
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $response = [
            'error' => false,
            'messages' => '',
        ];
        $parentOrder = $this->_initParentOrder();
        if (!$this->isValidPostRequest()
            || empty($parentOrder)
            || !$parentOrder->getId()
            || !$this->parentOrderManagement->canCancel($parentOrder)
        ) {
            $response['error'] = true;
            $this->messageManager->addErrorMessage(__('You can not canceled this order.'));
            return $resultJson->setData($response);
        }

        if(!$this->validateCancelParentOrder($parentOrder)){
            $response['error'] = true;
            return $resultJson->setData($response);
        }

        try {
            $cancelDetail = new DataObject($this->getRequest()->getParams());
            $this->parentOrderCancelAction->execute($parentOrder, $cancelDetail);
            $this->sendCancelEmail($parentOrder);
            $this->messageManager->addSuccessMessage(__('You canceled the order.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('You have not canceled the item.'));
            $this->_objectManager->get(\Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger::class)->critical($e);
        }
        return $resultJson->setData($response);
    }

    protected function validateCancelParentOrder($parentOrder)
    {
        $isValid = true;
        $subOrders = $parentOrder->getSubOrders();
        $notSubOrderExceptions = new \Magento\Framework\Exception\LocalizedException(
            __('Parent Order "%1" can not cancel : no sub-orders found',
                $parentOrder->getDetail()->getIncrementId()
            )
        );

        if (empty($subOrders)) {
            throw $notSubOrderExceptions;
        }

        foreach ($subOrders as $_order) {
            if($_order->getData('ecpay_invoice_tag') != 1){
                $isValid = false;
                $this->messageManager->addErrorMessage(__('Sorry, there was an operation error. Please try again later or contact customer service. Order Number:{%1（%2）}', $_order->getIncrementId(), $parentOrder->getDetail()->getIncrementId()));
            }
        }

        return $isValid;
    }

    public function sendCancelEmail($parentOrder)
    {
        try {
            $this->sender->send($parentOrder);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }
    }
}
