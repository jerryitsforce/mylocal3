<?php
namespace Branch8\Sales\Helper\Order;

use Branch8\HotaiCore\Model\Order\State as HotaiState;
use Branch8\HotaiCore\Model\Order\Status as HotaiStatus;
use Branch8\Sales\Model\Actions\ResyncOrdersToGrid;
use Psr\Log\LoggerInterface;
use \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail;
use \Branch8\Rma\Helper\RmaActions;
use \Exception;
use \Magento\Framework\Registry;
use \Magento\Sales\Api\OrderItemRepositoryInterface;
use \Magento\Sales\Model\OrderRepository;
use \Magento\Sales\Model\Order\ItemFactory;

class UpdateOrderStatus
{
    /**
     * @var Registry
     */
    protected $_coreRegistry = null;

    /** @var \Magento\Sales\Api\OrderItemRepositoryInterface $itemCollectionFactory */
    private $itemCollectionFactory;

    /** @var \Magento\Sales\Model\OrderRepository */
    protected $_orderRepository;

    /** @var \Branch8\Rma\Helper\RmaActions $rmaActions */
    protected $rmaActions;

    protected $itemFactory;

    public $flowStatus;

    private $detailResource;
    private \Magento\Sales\Model\ResourceModel\Order\Item $orderItemResource;
    private \Magento\Sales\Model\ResourceModel\Order $orderResource;
    private \Magento\Sales\Model\OrderFactory $orderFactory;
    private LoggerInterface $logger;
    private ResyncOrdersToGrid $resyncOrdersToGrid;

    /**
     * @param Registry $registry
     * @param OrderItemRepositoryInterface $itemCollectionFactory
     * @param OrderRepository $_orderRepository
     * @param RmaActions $rmaActions
     * @param ItemFactory $itemFactory
     * @param ParentOrderDetail $detailResource
     * @param \Magento\Sales\Model\ResourceModel\Order\Item $resource
     * @param \Magento\Sales\Model\ResourceModel\Order $resourceOrder
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Registry $registry,
        OrderItemRepositoryInterface $itemCollectionFactory,
        OrderRepository $_orderRepository,
        RmaActions $rmaActions,
        ItemFactory $itemFactory,
        ParentOrderDetail $detailResource,
        \Magento\Sales\Model\ResourceModel\Order\Item $resource,
        \Magento\Sales\Model\ResourceModel\Order $resourceOrder,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        ResyncOrdersToGrid $resyncOrdersToGrid,
        LoggerInterface $logger
    ) {
        $this->_coreRegistry         = $registry;
        $this->rmaActions            = $rmaActions;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->itemFactory           = $itemFactory;
        $this->_orderRepository      = $_orderRepository;
        $this->detailResource        = $detailResource;
        $this->orderItemResource     = $resource;
        $this->orderResource         = $resourceOrder;
        $this->orderFactory          = $orderFactory;
        $this->logger                = $logger;
        $this->resyncOrdersToGrid    = $resyncOrdersToGrid;
    }

    /**
     * @param $itemId
     * @param $status
     * @param $orderId
     * @param $createRma
     * @param $rmaId
     * @param $addItemStatusRecord
     * @return \Magento\Sales\Model\Order\Item
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function updateItemStatusById(
        $itemId,
        $status,
        $orderId,
        $createRma = false,
        $rmaId = null,
        $addItemStatusRecord = true
    ) {
        $item = $this->itemFactory->create()->load($itemId);
        if ($createRma) {
            $oriStatus                            = $item->getFlowStatus();
            $rmaOptions                           = is_null($item->getRmaOptions()) ? [] : json_decode($item->getRmaOptions(), true);
            $rmaOptions['flow_status_before_rma'] = $oriStatus;
            $rmaOptions['rma_id']                 = $rmaId;
            $item->setRmaOptions(json_encode($rmaOptions));
            $item->setRmaStatus(\Branch8\Rma\Model\Rma\Status::RMA_PROCESSING);
        }

        $item->setFlowStatus($status);
        $item->setDataChanges(true);
        $this->orderItemResource->save($item);
        $this->flowStatus = $status;
        /***************
         * bug when saving order , it also save order items
         * /***********/
        if ($addItemStatusRecord) {
            $this->addItemStatusRecord($orderId, $item, $status);
        }
        return $item;
    }

    /**
     * addItemStatusRecord
     *
     * @param int $orderId
     * @param mixed $item
     * @param string $status
     * @return void
     */
    public function addItemStatusRecord($orderId, $item, $status)
    {
        $name = $item->getName();

        $comment = __("Update Order Item: $name - Status To %1", $status);
        $order   = $this->orderFactory->create()->load($orderId);
        if (! $order->getId()) {
            return;
        }

        /** IF order is not paid */
        if (
            in_array(
                $status,
                [
                    HotaiStatus::STATUS_ARRIVED,
                    HotaiStatus::STATUS_PROCESSING,
                ]
            ) && in_array(
                $order->getStatus(),
                [
                    HotaiStatus::STATUS_PENDING_PAYMENT,
                    HotaiStatus::STATUS_PENDING,
                ]
            )
        ) {
            return;
        }

        /** bug-fix/ if item status = arrived but sub-order status = complete */
        if ($status == HotaiStatus::STATUS_ARRIVED
            && $order->getStatus() == HotaiStatus::STATUS_COMPLETE) {

            $order->setState(HotaiState::STATE_PROCESSING);
            $order->setStatus(HotaiStatus::STATUS_ARRIVED);
            // $order->setIsForceUpdateStatus(true);
            $order->save();
        }

        $orderStatus = $order->getStatus();
        $history     = $order->addStatusHistoryComment($comment, $orderStatus);
        $history->setItemId($item->getId());
        $history->setItemStatus($status);
        $history->save();
        //$this->orderResource->save($order);
        $order->save();
    }

    /**
     * cancelRmaApplication
     *
     * @param int $rmaId
     * @return void
     */
    public function cancelRmaApplication($rmaId)
    {
        $status = $this->rmaActions->cancelRmaApplication($rmaId);
        $item   = $this->rmaActions->getRmaItemCollection($rmaId);

        foreach ($item as $singleItem) {
            $this->addItemStatusRecord($singleItem->getOrderId(), $singleItem, $status);
        }

    }

    /**
     * setBackToFlowStatusBeforeRma
     *
     * @param int $itemId
     * @return void
     */
    public function setBackToFlowStatusBeforeRma($itemId)
    {
        //Item
        $item = $this->itemFactory->create()->load($itemId);
        if (is_null($item->getRmaOptions())) {
            throw new Exception(__('There is no preview flow status.'));
        }

        $rmaOptions = json_decode($item->getRmaOptions(), true);
        $oriStatus  = $rmaOptions['flow_status_before_rma'];
        $item->setFlowStatus($oriStatus);
        $item->setRmaStatus(\Branch8\Rma\Model\Rma\Status::RETURN_OR_EXCHANGE_AVALIABLE);
        $item->save();

        //Order
        $order   = $this->orderFactory->create()->load($item->getOrderId());

        if($order->getStatus() == HotaiStatus::STATUS_COMPLETE) {
            $order->setState(HotaiState::STATE_PROCESSING);
            $order->setStatus($oriStatus);
            $order->save();
        }

    }

    /**
     * getFlowStatus
     *
     * @return string | null
     */
    public function getFlowStatus()
    {
        return $this->flowStatus;
    }

    /**
     * updateSubOrderLatestStatus
     *
     * @param mixed $order
     * @return string
     */
    public function updateSubOrderLatestStatus($order)
    {
        $formalFlowStatusKey  = null;
        $reverseFlowStatusKey = null;
        $canceled             = true;
        foreach ($order->getAllVisibleItems() as $item) {

            // 只要有一張不是 canceled/failed delivery, 就不要變更為 canceled
            if (! in_array(
                $item->getFlowStatus(),
                [
                    HotaiStatus::STATUS_CANCELED,
                    HotaiStatus::STATUS_FAILED_DELIVERY,
                ]
            )) {
                $canceled = false;
            }

            if ($item->getRmaStatus() == \Branch8\Rma\Model\Rma\Status::RMA_PROCESSING) {
                //if item is in reverse flow
                $reverseFlowStatusKey = $this->getReverseFlowStatusKey(
                    $reverseFlowStatusKey,
                    $item->getFlowStatus(),
                    true
                );
                continue;
            }

            if (! in_array($item->getFlowStatus(), HotaiStatus::FORMAL_FLOW)) {
                continue;
            }

            $formalFlowStatusKey =
            $this->getFormalFlowStatusKey($formalFlowStatusKey, $item->getFlowStatus());
        }

        if ($canceled) {

            if ($order->getStatus() == HotaiStatus::STATUS_CANCELED) {
                return 'This order is canceled.';
            }

            $order->setState(HotaiState::STATE_CANCELED);
            $order->setStatus(HotaiStatus::STATUS_CANCELED);
            $statusComment = __("Cron Update Order Status to %1.", HotaiStatus::STATUS_CANCELED);
            $order->addCommentToStatusHistory($statusComment);
            $order->save();
            $this->resyncOrdersToGrid->execute([$order->getId()]);
            return 'This order is canceled.';
        }

        if (is_null($formalFlowStatusKey) && is_null($reverseFlowStatusKey)) {
            return 'There is no formal or reverse flow status. No Need To Update Status.';
        }

        $status = '';

        //If no formal flow item, then order status would be complete
        if (is_null($formalFlowStatusKey)
            && ! $this->isSameStatus($order->getStatus(), HotaiStatus::STATUS_COMPLETE)) {

            $this->setForceUpdateStatus($order);
            $order->setStatus(HotaiStatus::STATUS_COMPLETE);
            $order->setState(HotaiState::STATE_COMPLETE);
            $statusComment = __("Cron Update Order Status to %1.", HotaiStatus::STATUS_COMPLETE);
            $order->addCommentToStatusHistory($statusComment);
            $status = $status . '  ' . HotaiStatus::STATUS_COMPLETE;

        }

        if (! is_null($formalFlowStatusKey)
            && ! $this->isSameStatus($order->getStatus(), HotaiStatus::FORMAL_FLOW[$formalFlowStatusKey])) {

            $statusOrderCheck = $this->statusOrderCheck(
                array_keys(HotaiStatus::FORMAL_FLOW, $order->getStatus())[0],
                $formalFlowStatusKey
            );

            if (! $statusOrderCheck) {
                $statusComment = __(
                    "Order status cannot revert. " .
                    "Order status now: " . $order->getStatus() .
                    "Order status was expected to be: " . HotaiStatus::FORMAL_FLOW[$formalFlowStatusKey]);
                $order->addCommentToStatusHistory($statusComment);
                $order->save();

                return 'Status reverse. No need to update';
            }
            /**
             * set force status update to by-pass check state plugin
             * @see Branch8/Sales/Plugin/Magento/Sales/Model/ResourceModel/Order/Handler/StatePlugin.php
             */

            $this->setForceUpdateStatus($order, HotaiStatus::FORMAL_FLOW[$formalFlowStatusKey]);
            $order->setStatus(HotaiStatus::FORMAL_FLOW[$formalFlowStatusKey]);
            $order->setState(HotaiStatus::FORMAL_FLOW_STATE[$formalFlowStatusKey]);
            $statusComment = __("Cron Update Order Status to %1.", HotaiStatus::FORMAL_FLOW[$formalFlowStatusKey]);
            $order->addCommentToStatusHistory($statusComment);
            $status = $status . '  ' . HotaiStatus::FORMAL_FLOW[$formalFlowStatusKey];
        }

        if (! is_null($reverseFlowStatusKey)
            && ! $this->isSameStatus($order->getRmaStatus(), HotaiStatus::REVERSE_FLOW[$reverseFlowStatusKey])) {
            /**
             * set force status update to by-pass check state plugin
             * @see Branch8/Sales/Plugin/Maento/Sales/Model/ResourceModel/Order/Handler/StatePlugin.php
             */
            $this->setForceUpdateStatus($order);
            $order->setRmaStatus(HotaiStatus::REVERSE_FLOW[$reverseFlowStatusKey]);
            $statusComment = __("Cron Update Rma Status to %1.", HotaiStatus::REVERSE_FLOW[$reverseFlowStatusKey]);
            $order->addCommentToStatusHistory($statusComment);
            $status = $status . '  ' . HotaiStatus::REVERSE_FLOW[$reverseFlowStatusKey];
        }

        $order->save();
        $this->resyncOrdersToGrid->execute([$order->getId()]);
        if (empty($status)) {
            $status = 'Same Status. No need to update status';
        }

        return $status;
    }

    /**
     * updateParentOrderLatestStatus
     *
     * @param mixed $parentOrder
     * @return string
     */
    public function updateParentOrderLatestStatus($parentOrder)
    {
        $formalFlowStatusKey  = null;
        $reverseFlowStatusKey = null;
        $canceled             = true;

        foreach ($parentOrder->getSubOrders() as $order) {
            $formalFlowStatusKey =
            $this->getFormalFlowStatusKey($formalFlowStatusKey, $order->getStatus());

            $reverseFlowStatusKey =
            $this->getReverseFlowStatusKey($reverseFlowStatusKey, $order->getRmaStatus());

            // 只要有一張不是 canceled, 就不要變更為 canceled
            if ($order->getStatus() != HotaiStatus::STATUS_CANCELED) {
                $canceled = false;
            }
        }

        $detail = $parentOrder->getDetail();
        $status = '';

        if ($canceled && $detail->getStatus() != HotaiStatus::STATUS_CANCELED) {
            $detail->setStatus(HotaiStatus::STATUS_CANCELED);
            $detail->setState(HotaiState::STATE_CANCELED);
            $detail->save();
            return 'This parent order is canceled.';
        }

        if (is_null($formalFlowStatusKey) && is_null($reverseFlowStatusKey)) {
            return 'There is no formal or reverse flow status. No Need To Update Status.';
        }

        if (is_null($formalFlowStatusKey)
            && ! $this->isSameStatus($detail->getStatus(), HotaiStatus::STATUS_COMPLETE)) {

            $detail->setStatus(HotaiStatus::STATUS_COMPLETE);
            $detail->setState(HotaiState::STATE_COMPLETE);
            $status = $status . '  ' . HotaiState::STATE_COMPLETE;
        }

        if (! is_null($formalFlowStatusKey)
            && ! $this->isSameStatus($detail->getStatus(), HotaiStatus::FORMAL_FLOW[$formalFlowStatusKey])) {

            $statusOrderCheck = $this->statusOrderCheck(
                array_keys(HotaiStatus::FORMAL_FLOW, $detail->getStatus())[0],
                $formalFlowStatusKey
            );

            if (! $statusOrderCheck) {
                return 'Status reverse. No need to update';
            }

            $detail->setStatus(HotaiStatus::FORMAL_FLOW[$formalFlowStatusKey]);
            $detail->setState(HotaiStatus::FORMAL_FLOW_STATE[$formalFlowStatusKey]);
            $status = $status . '  ' . HotaiStatus::FORMAL_FLOW[$formalFlowStatusKey];
        }

        if (! is_null($reverseFlowStatusKey)
            && ! $this->isSameStatus($detail->getRmaStatus(), HotaiStatus::REVERSE_FLOW[$reverseFlowStatusKey])) {

            $detail->setRmaStatus(HotaiStatus::REVERSE_FLOW[$reverseFlowStatusKey]);
            $status = $status . '  ' . HotaiStatus::REVERSE_FLOW[$reverseFlowStatusKey];
        }

        $this->detailResource->save($detail);

        if (empty($status)) {
            $status = 'Same Status. No need to update status';
        }

        return $status;
    }

    /**
     * getFormalFlowStatusKey
     *
     * @param int|null $formalFlowStatusKey
     * @param string $status
     * @return int | null
     */
    protected function getFormalFlowStatusKey($formalFlowStatusKey, $status)
    {
        $itemStatusKey = array_keys(HotaiStatus::FORMAL_FLOW, $status);

        if (empty($itemStatusKey)) {
            return $formalFlowStatusKey;
        }

        if (is_null($formalFlowStatusKey)) {
            $formalFlowStatusKey = $itemStatusKey[0];
        }

        if ($itemStatusKey[0] < $formalFlowStatusKey) {
            $formalFlowStatusKey = $itemStatusKey[0];
        }

        return $formalFlowStatusKey;
    }

    /**
     * getReverseFlowStatusKey
     *
     * @param string| null|int $reverseFlowStatusKey
     * @param string $status
     * @param bool $subOrder
     * @return string | null
     */
    protected function getReverseFlowStatusKey($reverseFlowStatusKey, $status, $subOrder = false)
    {

        if (is_null($status)) {
            return $reverseFlowStatusKey;
        }

        if ($subOrder) {
            $itemStatusKey = $this->getRmaStatusKey($status);
        } else {
            $itemStatusKey = array_keys(HotaiStatus::REVERSE_FLOW, $status);
        }

        if (empty($itemStatusKey)) {
            return $reverseFlowStatusKey;
        }

        if (is_null($reverseFlowStatusKey)) {
            $reverseFlowStatusKey = $itemStatusKey[0];
        }

        if ($itemStatusKey[0] < $reverseFlowStatusKey) {
            $reverseFlowStatusKey = $itemStatusKey[0];
        }

        return $reverseFlowStatusKey;
    }

    /**
     * getRmaStatusKey
     *
     * @param string $status
     * @return string
     */
    private function getRmaStatusKey($status)
    {
        if (in_array($status, HotaiStatus::REVERSE_FLOW_PROCESSING)) {
            $status = array_keys(HotaiStatus::REVERSE_FLOW, HotaiStatus::STATUS_RMA_PROCESSING);
            return $status;
        }

        if (in_array($status, HotaiStatus::REVERSE_FLOW_COMPLETE)) {
            $status = array_keys(HotaiStatus::REVERSE_FLOW, HotaiStatus::STATUS_RMA_COMPLETED);
            return $status;
        }

        if (in_array($status, HotaiStatus::REVERSE_FLOW_DECLINE)) {
            $status = array_keys(HotaiStatus::REVERSE_FLOW, HotaiStatus::STATUS_RMA_FAILED);
            return $status;
        }

        $status = array_keys(HotaiStatus::REVERSE_FLOW, HotaiStatus::STATUS_RMA_OTHER);
        return $status;
    }

    /**
     * isSameStatus
     *
     * @param  string $oldStatus
     * @param  string $newStatus
     * @return bool
     */
    protected function isSameStatus($oldStatus, $newStatus)
    {
        return $oldStatus == $newStatus;
    }

    /**
     * setForceUpdateStatus
     *
     * @param  mixed $order
     * @param  string $upcomingStatus
     * @return  $order
     */
    protected function setForceUpdateStatus($order, $upcomingStatus = null)
    {
        if ($order->getStatus() == HotaiStatus::STATUS_COMPLETE) {
            return;
        }

        if ($order->getStatus() == HotaiStatus::STATUS_CLOSED) {
            return;
        }

        if ($order->getIsVirtual()) {
            return;
        }

        if (in_array(
            $upcomingStatus,
            [
                HotaiStatus::STATUS_SHIPPING,
                HotaiStatus::STATUS_ARRIVED,
                HotaiStatus::STATUS_PICKED,
            ]
        )) {
            return;
        }

        $order->setIsForceUpdateStatus(true);
        return $order;
    }

    protected function statusOrderCheck($oldStatusOrder, $newStatusOrder)
    {
        return (int) $oldStatusOrder < (int) $newStatusOrder;
    }

    public function updateItemStatusBySubOrderStatus($order)
    {
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($order->getAllVisibleItems() as $item) {

            if ($order->getStatus() == HotaiStatus::STATUS_TALLYING) {
                $item->setRmaStatus(\Branch8\Rma\Model\Rma\Status::RETURN_OR_EXCHANGE_AVALIABLE);
            }

            $item->setFlowStatus($order->getStatus());
            $item->save();
            $this->addItemStatusRecord($order->getId(), $item, $order->getStatus());
        }
    }
}
