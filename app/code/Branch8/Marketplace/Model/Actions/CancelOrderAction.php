<?php
declare (strict_types = 1);

namespace Branch8\Marketplace\Model\Actions;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\MarketPlaceParentOrder\Model\ParenOrderManagement;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Manager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Webkul\Marketplace\Model\OrdersFactory as MpOrdersModel;
use Webkul\Marketplace\Model\SaleslistFactory;
use Branch8\Marketplace\Service\MarketplaceLogger;

/**
 *
 */
class CancelOrderAction implements CancelOrderActionInterface
{
    private \Webkul\Marketplace\Helper\Orders $helper;
    private Manager $eventManager;
    private \Webkul\Marketplace\Helper\Orders $orderHelper;
    private \Magento\Sales\Model\Service\OrderService $orderService;
    private MarketplaceLogger $marketplaceLogger;
    private ParenOrderManagement $parentOrderManagement;
    /**
     * @var SaleslistFactory
     */
    private $saleslistFactory;
    /**
     * @var MpOrdersModel
     */
    protected $mpOrdersModel;

    private $connection;

    private OrderRepository $orderRepository;

    /**
     * @param \Webkul\Marketplace\Helper\Orders $helper
     * @param \Magento\Sales\Model\Service\OrderService $orderService
     * @param MarketplaceLogger $marketplaceLogger
     * @param ParenOrderManagement $parenOrderManagement
     * @param SaleslistFactory $saleslistFactory
     * @param Manager $eventManager
     * @param ResourceConnection $resourceConnection
     * @param MpOrdersModel $mpOrdersModel
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        \Webkul\Marketplace\Helper\Orders $helper,
        \Magento\Sales\Model\Service\OrderService $orderService,
        MarketplaceLogger $marketplaceLogger,
        ParenOrderManagement $parenOrderManagement,
        \Webkul\Marketplace\Model\SaleslistFactory $saleslistFactory,
        Manager $eventManager,
        MpOrdersModel $mpOrdersModel,
        ResourceConnection $resourceConnection,
        OrderRepository $orderRepository
    ) {
        $this->orderService = $orderService;
        $this->eventManager = $eventManager;
        $this->orderHelper = $helper;
        $this->parentOrderManagement = $parenOrderManagement;
        $this->marketplaceLogger = $marketplaceLogger;
        $this->mpOrdersModel = $mpOrdersModel;
        $this->saleslistFactory = $saleslistFactory;
        $this->orderRepository = $orderRepository;
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * @return $this
     */
    public function beginTransaction()
    {
        $this->connection->beginTransaction();
        return $this;
    }

    /**
     * @return $this
     */
    public function commitTransation()
    {
        $this->connection->commit();
        return $this;
    }

    /**
     * @return $this
     */
    public function rollBack()
    {
        $this->connection->rollBack();
        return $this;
    }

    /**
     * @param Order $order
     * @param $sellerId
     * @return $this|mixed
     * @throws LocalizedException
     */
    public function execute(
        \Magento\Sales\Model\Order $order,
        $sellerId = null
    ) {
        $state = $order->getState();
        $status = $order->getStatus();
        switch ($status) {
            case \Magento\Sales\Model\Order::STATE_PROCESSING:
            case \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT:
                $flag = $this->cancelOrder($order, $sellerId);
                break;
            default:
                throw new LocalizedException(__('Invalid order state to cancel order'));
        }
        return $this;
    }

    /**
     * @param $orderId
     * @param $sellerId
     * @return $this
     */
    public function updateTrackingCollection(Order $order, $sellerId = null)
    {
        if (!$order->getId() && $sellerId) {
            return $this;
        }
        $trackingcoll = $this->mpOrdersModel->create()
            ->getCollection()
            ->addFieldToFilter(
                'order_id',
                $order->getId()
            )
            ->addFieldToFilter(
                'seller_id',
                $sellerId
            );
        foreach ($trackingcoll as $tracking) {
            $tracking->setTrackingNumber('canceled');
            $tracking->setCarrierName('canceled');
            $tracking->setIsCanceled(1);
            $tracking->setOrderStatus('canceled');
            $tracking->save();
        }
        return $this;
    }

    /**
     * @param Order $order
     * @param DataObject $postSubmit
     * @return CancelOrderAction
     * @throws \Exception
     */
    public function saveCancelReason(Order $order, DataObject $postSubmit = null)
    {
        if (!$postSubmit) {
            return $this;
        }
        $reason = $postSubmit->getData('reason');
        $reasonDescription = $postSubmit->getData('reason_description');
        $comment = $reason . ': ' . $reasonDescription;
        $this->parentOrderManagement->setComment($comment);
        $this->parentOrderManagement->updateSubOrderStatus(
            $order, Status::STATUS_CANCELED
        );
        return $this;
    }

    /**
     * @param Order $order
     * @param $status
     * @return $this
     */
    public function updateOrderItemFlowStatus(Order $order, $status = Status::STATUS_CANCELED)
    {
        foreach ($order->getItemsCollection() as $item) {
            if ($status == Status::STATUS_CANCELED
                && $item->getFlowStatus() == Status::STATUS_FINANCIAL_REVIEW) {
                continue;
            }

            $item->setFlowStatus($status);
            $item->save();
        }
        return $this;
    }

    /**
     * @param Order $order
     * @param $sellerId
     * @return $this
     */
    public function updateSellerOrderStatus(Order $order, $sellerId)
    {
        if (!$sellerId) {
            return $this;
        }
        $orderId = $order->getId();
        $paidCanceledStatus = \Webkul\Marketplace\Model\Saleslist::PAID_STATUS_CANCELED;
        $paymentCode = '';
        if ($order->getPayment()) {
            $paymentCode = $order->getPayment()->getMethod();
        }
        $collection = $this->saleslistFactory->create()
            ->getCollection()
            ->addFieldToFilter(
                'order_id',
                ['eq' => $orderId]
            )
            ->addFieldToFilter(
                'seller_id',
                ['eq' => $sellerId]
            );
        foreach ($collection as $saleproduct) {
            $saleproduct->setCpprostatus(
                $paidCanceledStatus
            );
            $saleproduct->setPaidStatus(
                $paidCanceledStatus
            );
            if ($paymentCode == 'mpcashondelivery') {
                $saleproduct->setCollectCodStatus(
                    $paidCanceledStatus
                );
                $saleproduct->setAdminPayStatus(
                    $paidCanceledStatus
                );
            }
            $saleproduct->save();
        }
        return $this;
    }

    /**
     * @param Order $order
     * @param int $sellerId
     * @return bool|int|\Webkul\Marketplace\Helper\boool
     * @throws LocalizedException
     */
    private function cancelOrder(\Magento\Sales\Model\Order $order, $sellerId = null)
    {
        // if (!$order->canCancel()) {
        //     throw new LocalizedException(__('Cannot cancel order.'));
        // }
        $flag = false;
        /**
         *
         */
        if ($sellerId) {
            if (!$order->getData('is_paid')) {
                $order->cancel();
                $this->orderRepository->save($order);
            } else {
                $this->eventManager->dispatch(
                    'sales_order_cancel_paied_order',
                    ['order' => $order]
                );
            }

            $flag = true;
        } else {
            try {
                $flag = $this->orderService->cancel($order->getId());
            } catch (\Exception $exception) {
                $this->marketplaceLogger->logException('CancelOrderAction', $exception, [
                    'order_id' => $order->getId(),
                ]);
                $flag = false;
            }
        }
        if (!$flag) {
            throw new LocalizedException(__('Cannot cancel order.'));
        }
        return $flag;
    }
}
