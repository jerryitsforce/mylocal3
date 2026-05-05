<?php
namespace Branch8\Sales\Observer;

use Branch8\HotaiPoint\Helper\CacheLock as PointCacheLock;
use Ecpay\General\Cron\OrderAutoProcedure;
use JsonException;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\FileSystemException;
use Zend_Log_Exception;
use Magento\Sales\Api\OrderRepositoryInterface;

class PointOnlyOrderIsPaid implements ObserverInterface
{
    protected OrderAutoProcedure $orderAutoProcedure;
    protected ManagerInterface $eventManager;
    protected PointCacheLock $pointCacheLock;
    protected OrderRepositoryInterface $orderRepository;

    public function __construct(
        OrderAutoProcedure $orderAutoProcedure,
        ManagerInterface $eventManager,
        PointCacheLock $pointCacheLock,
        OrderRepositoryInterface $orderRepository,
    ) {
        $this->orderAutoProcedure = $orderAutoProcedure;
        $this->eventManager       = $eventManager;
        $this->pointCacheLock     = $pointCacheLock;
        $this->orderRepository    = $orderRepository;
    }

    /**
     * @throws Zend_Log_Exception
     * @throws FileSystemException
     * @throws JsonException
     */
    public function execute($observer)
    {

        $order = $observer->getEvent()->getData('order');
        if (
            $order->getData('grand_total') == 0/*&& (int)$order->getData('point_discount_total') > 0*/
            && $order->getState() == \Branch8\HotaiCore\Model\Order\State::STATE_PROCESSING
            && (int) $order->getData('is_paid') == 0
        ) {
            $this->pointCacheLock->pointCommitProcedureLock($order->getId());

            $order->setData('is_paid', 1);
            $this->orderRepository->save($order);

            $this->eventManager->dispatch(
                "ecpay_invoice_issued_for_paid",
                [
                    "orderId" => $order->getId(),
                ]
            );

            /** 贈禮訂單如果還沒有變成 processing 就不用分配票券 */
            if ($order->getData('is_gift_order')
                && $order->getStatus() != \Branch8\HotaiCore\Model\Order\Status::STATUS_PROCESSING) {
                return;
            }

            $this->eventManager->dispatch(
                "ecpay_inovice_ticket_item_arrived_check",
                [
                    "orderId" => $order->getId(),
                ]
            );

            // Let it realse by time.
            // $this->pointCacheLock->pointCommitProcedureUnlock($order->getId());

            // $this->orderAutoProcedure->invoiceAutoProcess($order->getId());
        }
    }
}