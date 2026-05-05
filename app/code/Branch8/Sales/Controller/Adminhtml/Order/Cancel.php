<?php
declare (strict_types=1);

namespace Branch8\Sales\Controller\Adminhtml\Order;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Marketplace\Model\Actions\CancelOrderAction;
use Branch8\Marketplace\Model\Actions\CancelOrderActionInterface;
use Branch8\Marketplace\Model\Actions\GetSellerByOrder;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;


class Cancel extends \Magento\Sales\Controller\Adminhtml\Order implements HttpPostActionInterface
{
    /**
     * @var UpdateOrderStatus
     */
    protected $updateOrderStatus;
    private CancelOrderAction $cancelOrderAction;
    private GetSellerByOrder $getSellerByOrder;

    /**
     * @param Action\Context $context
     * @param Registry $coreRegistry
     * @param FileFactory $fileFactory
     * @param InlineInterface $translateInline
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $resultLayoutFactory
     * @param RawFactory $resultRawFactory
     * @param OrderManagementInterface $orderManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param UpdateOrderStatus $updateOrderStatus
     * @param CancelOrderAction $cancelOrderAction
     * @param GetSellerByOrder $getSellerByOrder
     */
    public function __construct(
        Action\Context           $context,
        Registry                 $coreRegistry,
        FileFactory              $fileFactory,
        InlineInterface          $translateInline,
        PageFactory              $resultPageFactory,
        JsonFactory              $resultJsonFactory,
        LayoutFactory            $resultLayoutFactory,
        RawFactory               $resultRawFactory,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface          $logger,
        UpdateOrderStatus        $updateOrderStatus,
        CancelOrderAction        $cancelOrderAction,
        GetSellerByOrder         $getSellerByOrder,
    )
    {
        $this->updateOrderStatus = $updateOrderStatus;
        $this->cancelOrderAction = $cancelOrderAction;
        $this->getSellerByOrder = $getSellerByOrder;
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $translateInline,
            $resultPageFactory,
            $resultJsonFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $orderManagement,
            $orderRepository,
            $logger
        );
    }

    /**
     * Update Order Status
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $postData = $this->getRequest()->getParams();
        $result = ['error' => '', 'errorcode' => ''];
        $return = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        if (!$this->getRequest()->isPost() || empty($postData)) {
            $this->messageManager->addError(__('Something went wrong.'));
            return $result->setData($result);
        }
        try {
            $order = $this->_initOrder();
            $orderId = $order->getId();
            $seller = $this->getSellerByOrder->execute($order);
            $reason = new DataObject($this->getRequest()->getParams());
            $sellerId = $seller ? $seller->getId() : null;
            $this->cancelOrderAction
                ->beginTransaction()
                ->execute($order, $sellerId)
                ->saveCancelReason($order, $reason)
                ->updateTrackingCollection($order, $sellerId)
                ->updateOrderItemFlowStatus($order, $status = Status::STATUS_CANCELED)
                ->updateSellerOrderStatus($order, $sellerId)
                ->commitTransation();
            $this->_eventManager->dispatch(
                'mp_order_cancel_after',
                ['seller_id' => $sellerId, 'order' => $order]
            );
            $this->messageManager->addSuccessMessage(__('Cancel order successfully.'));
        } catch (\Exception $e) {
            $this->cancelOrderAction->rollBack();
            $this->messageManager->addErrorMessage(__('Error when cancel order'));
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            $this->logger->critical($e->getMessage());
            $this->logger->critical($e->getTraceAsString());
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $return->setData($result);
    }
}
