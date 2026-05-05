<?php
namespace Branch8\MarketPlaceParentOrder\Model\Services;

use Branch8\HotaiCore\Helper\Status as HotaiCoreStatusHelper;
use Branch8\HotaiCore\Model\Order\Status as HotaiOrderStatus;
use Branch8\HotaiPoint\Helper\CacheLock as PointCacheLock;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Psr\Log\LoggerInterface;

/**
 * ProcessParentOrdersWhenCheckoutWithPoint
 */
class ProcessParentOrdersWhenCheckoutWithFullPoint
{
    const METHOD_FREE = 'free';
    public InvoiceService $invoceService;
    public LoggerInterface $logger;
    private \Magento\Framework\DB\Transaction $transaction;
    public ResourceConnection $resourceConnection;
    private \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus;
    public EventManager $eventManager;
    public PointCacheLock $pointCacheLock;
    public HotaiCoreStatusHelper $hotaiCoreStatusHelper;

    /**
     * @param InvoiceService $invoiceService
     * @param LoggerInterface $logger
     * @param \Magento\Framework\DB\Transaction $transaction
     * @param ResourceConnection $resourceConnection
     * @param EventManager $eventManager
     * @param PointCacheLock $pointCacheLock
     * @param HotaiCoreStatusHelper $hotaiCoreStatusHelper
     */
    public function __construct(
        InvoiceService $invoiceService,
        LoggerInterface $logger,
        \Magento\Framework\DB\Transaction $transaction,
        ResourceConnection $resourceConnection,
        EventManager $eventManager,
        PointCacheLock $pointCacheLock,
        HotaiCoreStatusHelper $hotaiCoreStatusHelper
    ) {
        $this->resourceConnection    = $resourceConnection;
        $this->transaction           = $transaction;
        $this->logger                = $logger;
        $this->invoceService         = $invoiceService;
        $this->eventManager          = $eventManager;
        $this->pointCacheLock        = $pointCacheLock;
        $this->hotaiCoreStatusHelper = $hotaiCoreStatusHelper;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return false|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(ParentOrder $parentOrder)
    {
        /**
         *
         */
        if ($parentOrder->getDetail()->getPaymentMethod() !== self::METHOD_FREE) {
            return false;
        }

        $subOrders = $parentOrder->getSubOrders();
        $count     = count($subOrders);
        if ($subOrders && ! $count) {
            return false;
        }
        /**
         * //1. Create invoice for all sub orders
         * 2. Update item flow status corresponding
         *    2.1 Update sales_order_item.flow_status to "arrived" when sub order has no shipping
         *    2.2 Update sales_order_item.flow_status to "processing" when sub order has shipping
         */
        /**
         * @var $subOrder Order
         */
        foreach ($subOrders as $subOrder) {
            $hasShipping = $subOrder->getShippingAddress() || $subOrder->getIsVirtual() === false;
            // try {
            //     $invoice = $this->invoceService->prepareInvoice($subOrder);
            //     $invoice->register();
            //     $order = $invoice->getOrder();
            //     $order->setIsInProcess(true);
            //     $this->transaction->addObject($invoice)->addObject($order)->save();
            // } catch (\Exception $exception) {
            //     $this->logger->critical($exception->getMessage());
            // }
            $this->updateSaleItemFlow($subOrder, $hasShipping);
        }
    }

    /**
     * @param Order $subOrder
     * @param $hasShipping
     * @return void
     */
    private function updateSaleItemFlow(Order $subOrder, $hasShipping)
    {
        $this->updateOrderStatus = ObjectManager::getInstance()->get(UpdateOrderStatus::class);
        $itemNeedUpdate          = [];
        $orderId                 = $subOrder->getId();
        $status                  = HotaiOrderStatus::STATUS_PROCESSING;

        if ($subOrder->getIsGiftOrder()) {
            $status = HotaiOrderStatus::STATUS_GIFT_INFO_PENDING;
        }

        foreach ($subOrder->getAllItems() as $item) {
            $oldStatus = $item->getFlowStatus();

            if ($oldStatus == HotaiOrderStatus::STATUS_GIFT_INFO_PENDING) {
                continue;
            }

            if (! $this->hotaiCoreStatusHelper->checkFlowStatusSequenceForChange($oldStatus, $status, $subOrder, true, __CLASS__)) {
                continue;
            }

            $itemNeedUpdate[] = [
                'item_id'     => $item->getId(),
                'flow_status' => $status,
            ];
        }
        if ($itemNeedUpdate) {
            $this->resourceConnection->getConnection()->insertOnDuplicate(
                'sales_order_item',
                $itemNeedUpdate,
                ['flow_status']
            );
            foreach ($subOrder->getAllItems() as $item) {
                $this->updateOrderStatus->addItemStatusRecord(
                    $orderId,
                    $item,
                    $status
                );
            }
        }
    }
}
