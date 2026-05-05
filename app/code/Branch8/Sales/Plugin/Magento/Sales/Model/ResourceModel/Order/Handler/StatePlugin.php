<?php
declare(strict_types=1);


namespace Branch8\Sales\Plugin\Magento\Sales\Model\ResourceModel\Order\Handler;

use Branch8\HotaiCore\Model\Order\State;
use Branch8\Sales\Model\SubOrderStatusResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;
use Branch8\HotaiCore\Model\Order\Status;

/**
 * Checking order status and adjusting order status before saving
 */
class StatePlugin
{
    const XML_PATH_KEEP_VIRTUAL_ODER_STATUS_WHEN_CREATE_INVOICE = "b8sales/order_status/keep_virtual_order_status";

    private ScopeConfigInterface $scopeConfig;
    /**
     * @var SubOrderStatusResolver
     */
    private SubOrderStatusResolver $statusResolver;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param SubOrderStatusResolver $statusResolver
     */
    public function __construct(
        ScopeConfigInterface   $scopeConfig,
        SubOrderStatusResolver $statusResolver
    )
    {
        $this->statusResolver = $statusResolver;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    private function isEnalbed()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_KEEP_VIRTUAL_ODER_STATUS_WHEN_CREATE_INVOICE);
    }

    /**
     * @param $subject
     * @param callable $process
     * @param Order $order
     * @return void
     */

    public function aroundCheck($subject, callable $process, Order $order)
    {

        if ($order->getIsVirtual()) {
            return $subject;
        }
        /************
         * order is new or order updated by cron , keep as origin
         */
        if ($order->getIsForceUpdateStatus()) {
            return $process($order);
        }
        $currentState = $order->getState();
        $oldStatus = $order->getOrigData('status');
        $newStatus = $order->getStatus();
        if ($newStatus === Status::STATUS_FAILED_DELIVERY) {
            
            $order->setState(State::STATE_CANCELED);
            $order->setStatus(Status::STATUS_CANCELED);

            if ($order->getData('is_paid') && $order->getEcpayInvoiceCustomerIdentifier()){
                $order->setState(State::STATE_CANCEL_PENDING);
                $order->setStatus(Status::STATUS_CANCEL_PENDING);
            }
            
            return $subject;
        }
        // keep order as processing when create invoice + shipment +credit nemo
        if ($currentState === \Magento\Sales\Model\Order::STATE_PROCESSING) {
            return $subject;
        }
        return $subject;
    }
}
