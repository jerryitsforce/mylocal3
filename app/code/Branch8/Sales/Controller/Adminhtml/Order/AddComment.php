<?php
declare(strict_types=1);

namespace Branch8\Sales\Controller\Adminhtml\Order;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\HotaiCore\Model\Order\State;
use Branch8\Marketplace\Model\Actions\OrderFailedDeliveryHandle;
use Branch8\Sales\Helper\Data;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Psr\Log\LoggerInterface;
use Magento\Backend\App\Action;

/**
 * Class AddComment
 *
 * Controller responsible for addition of the order comment to the order
 */
class AddComment extends \Magento\Sales\Controller\Adminhtml\Order\AddComment
{
    private $updateOrderStatus;
    /**
     * @var OrderFailedDeliveryHandle
     */
    private OrderFailedDeliveryHandle $orderFailedDeliveryhandle;
    private \Magento\Backend\Model\Auth\Session $adminSession;

    private $helper;

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
     * @param UpdateOrderStatus $updateOrderStatus
     * @param OrderFailedDeliveryHandle $orderFailedeliveryHandle
     * @param \Magento\Backend\Model\Auth\Session $adminAuthSession
     * @param Data $helper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context                      $context,
        Registry                            $coreRegistry,
        FileFactory                         $fileFactory,
        InlineInterface                     $translateInline,
        PageFactory                         $resultPageFactory,
        JsonFactory                         $resultJsonFactory,
        LayoutFactory                       $resultLayoutFactory,
        RawFactory                          $resultRawFactory,
        OrderManagementInterface            $orderManagement,
        OrderRepositoryInterface            $orderRepository,
        UpdateOrderStatus                   $updateOrderStatus,
        OrderFailedDeliveryHandle           $orderFailedeliveryHandle,
        \Magento\Backend\Model\Auth\Session $adminAuthSession,
        Data                                $helper,
        LoggerInterface                     $logger
    )
    {
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
        $this->adminSession = $adminAuthSession;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->orderFailedDeliveryhandle = $orderFailedeliveryHandle;
        $this->helper = $helper;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $order = $this->_initOrder();
        $oldStatus = $order->getStatus();
        if ($order) {
            try {
                $data = $this->getRequest()->getPost('history');
                if (empty($data['comment']) && $data['status'] == $order->getDataByKey('status')) {
                    $error = 'Please provide a comment text or ' .
                        'update the order status to be able to submit a comment for this order.';
                    throw new \Magento\Framework\Exception\LocalizedException(__($error));
                }

                $orderStatus = $data['status'];
                $order->setStatus($orderStatus);
                if($orderStatus === Status::STATUS_COMPLETE) {
                    $order->setState(State::STATE_COMPLETE);
                }
                $notify = $data['is_customer_notified'] ?? false;
                $visible = $data['is_visible_on_front'] ?? false;
                if ($notify && !$this->_authorization->isAllowed(self::ADMIN_SALES_EMAIL_RESOURCE)) {
                    $notify = false;
                }
                $comment = trim(strip_tags($data['comment']));
                if ($orderStatus === OrderFailedDeliveryHandle::FAILED_DELIVERY_STATUS) {
                    $status = $this->orderFailedDeliveryhandle->execute($order, $comment);
                    if (!$status) {
                        throw new LocalizedException(__('Unknown error'));
                    }
                }
                //
                if ($orderStatus !== $order->getOriginData('status')) {
                    $this->helper->addWhoUpdateOrderStatus($order,
                        $this->adminSession->getUser()->getName(),
                        $order->getOriginData('status'),
                        $orderStatus
                    )->save();
                }
                $history = $order->addStatusHistoryComment($comment, $orderStatus);
                $history->setIsVisibleOnFront($visible);
                $history->setIsCustomerNotified($notify);
                foreach ($order->getItems() as $item) {
                    if ($order->getStatus() == Status::STATUS_TALLYING) {
                        $item->setRmaStatus(\Branch8\Rma\Model\Rma\Status::RETURN_OR_EXCHANGE_AVALIABLE);
                    }
                    $item->setFlowStatus($orderStatus);
                }
               // $history->save();
                $order->save();
                /// save history for item
                foreach ($order->getItems() as $item) {
                    $this->updateOrderStatus->addItemStatusRecord(
                        $order->getEntityId(), $item, $orderStatus
                    );
                }
                /** @var OrderCommentSender $orderCommentSender */
                $orderCommentSender = $this->_objectManager
                    ->create(\Magento\Sales\Model\Order\Email\Sender\OrderCommentSender::class);
                $orderCommentSender->send($order, $notify, $comment);

                return $this->resultPageFactory->create();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $response = ['error' => true, 'message' => $e->getMessage()];
            } catch (\Exception $e) {
                $response = ['error' => true, 'message' => __('We cannot add order history.')];
            }
            $resultJson = $this->resultJsonFactory->create();
            $resultJson->setData($response);
            return $resultJson;
        }
        return $this->resultRedirectFactory->create()->setPath('sales/*/');
    }

}
