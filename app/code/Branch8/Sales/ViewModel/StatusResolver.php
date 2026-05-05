<?php
declare(strict_types=1);

namespace Branch8\Sales\ViewModel;

use Branch8\HotaiCore\Model\Order\State as HotaiOrderState;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Branch8\HotaiCore\Model\Order\Status as HotaiOrderStatus;
use Magento\Sales\Model\Order;
use Webkul\MpRmaSystem\Model\Details;

class StatusResolver implements ArgumentInterface
{
    const ACL_PATH = 'Magento_Sales::adjust_shipping_status';
    const ACL_RMA_MANAGE_PATH = 'Webkul_MpRmaSystem::manage_rma';
    private \Magento\Framework\AuthorizationInterface $authorization;
    private \Magento\Sales\Model\Order\Config $orderConfig;

    /**
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     */
    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Sales\Model\Order\Config         $orderConfig
    )
    {
        $this->orderConfig = $orderConfig;
        $this->authorization = $authorization;
    }

    public function canModifyParentOrdertatus()
    {
        /**
         * https://trello.com/c/zKU0Ol9x/1798-q2admin-paneladjust-shipping-status?filter=label:Dev-Andy
         * Exception Case: This adjustment cannot be made if the invoice has already been voided—once voided,
         * the order Status cannot be modified.
         *
         */
        return $this->authorization->isAllowed(self::ACL_PATH);
    }

    /**
     * @return bool
     */
    public function canModifyOrdertatus(Order $order)
    {
        /**
         * https://trello.com/c/zKU0Ol9x/1798-q2admin-paneladjust-shipping-status?filter=label:Dev-Andy
         * Exception Case: This adjustment cannot be made if the invoice has already been voided—once voided,
         * the order Status cannot be modified.
         *
         */
        $havePermission = $this->authorization->isAllowed(StatusResolver::ACL_PATH);
        $invoiceCollection = $order->getInvoiceCollection();
        $hasInvoiceVoided = false;
        /**
         * @var $invoice \Magento\Sales\Model\Order\Invoice
         */
        foreach ($invoiceCollection as $invoice) {
            if ($invoice->getState() === Order\Invoice::STATE_CANCELED) {
                $hasInvoiceVoided = true;
            }
        }
        return $havePermission && $hasInvoiceVoided === false;
    }

    /**
     * @return bool
     */
    public function canAdjustShippingStatus()
    {
        return (bool)$this->authorization->isAllowed(StatusResolver::ACL_PATH);

    }

    /**
     * @return bool
     */
    public function canChangeRmaStatus()
    {
        return (bool)$this->authorization->isAllowed(self::ACL_RMA_MANAGE_PATH);
    }

    /**
     * @param Details $details
     * @return bool
     */
    public function canAdjustRmaStatus(Details $details)
    {
        $status = $details->getStatus();
        $canAdjust = true;
        $canNotModifyStatus = [
            RmaStatus::RETURN_APPLY_PROCESSING,
            RmaStatus::RETURNED
        ];
        if (in_array($status, $canNotModifyStatus)) {
            $canAdjust = false;
        }
        return $canAdjust;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function resolveStatusForNativeOrder(\Magento\Sales\Model\Order $order)
    {
        $state = $order->getState();
        $default = $this->orderConfig->getStateStatuses($state);
        if ($state !== HotaiOrderState::STATE_PROCESSING) {
            return $default;
        }

        /** add complete state */
        $completState = $this->orderConfig->getStateStatuses(HotaiOrderState::STATE_COMPLETE);
        $default[HotaiOrderState::STATE_COMPLETE] = $completState[HotaiOrderState::STATE_COMPLETE];
        
        $shippingMethod = $order->getShippingMethod();
        $currentStatus = $order->getStatus();
        $actions = $this->getDesiredStatus($currentStatus, $shippingMethod);
        $sort = $this->sortStatuesByKey($actions, $default);
        return $sort;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return array
     */
    public function resolveStatusForParentOrder(ParentOrder $parentOrder)
    {
        $state = $parentOrder->getDetail()->getState();
        $detail = $parentOrder->getDetail();
        $default = $this->orderConfig->getStateStatuses($state);
        if ($detail->getState() !== HotaiOrderState::STATE_PROCESSING) {
            return $default;
        }
        $currentStatus = $detail->getStatus();
        $actions = $this->getDesiredStatus($currentStatus);
        $sort = $this->sortStatuesByKey($actions, $default);
        return $sort;
    }

    /**
     * @param $actions
     * @param $default
     * @return array
     */
    private function sortStatuesByKey($actions, $default)
    {
        $sort = [];
        if ($actions) {
            $filtered = array_filter($default, function ($v, $k) use ($actions) {
                return in_array($k, $actions);
            }, ARRAY_FILTER_USE_BOTH);
            foreach ($actions as $key) {
                if (isset($filtered[$key])) {
                    $sort[$key] = $filtered[$key];
                }
            }
        }
        return $sort;
    }

    /**
     * @param $status
     * @return array
     */
    private function getDesiredStatus($status, $shippingMethod = '')
    {
        $needStatues = [];
        switch ($status) {
            case HotaiOrderStatus::STATUS_PROCESSING:
                $needStatues = [
                    HotaiOrderStatus::STATUS_TALLYING,
                    HotaiOrderStatus::STATUS_SHIPPING,
                    HotaiOrderStatus::STATUS_ARRIVED,
                    HotaiOrderStatus::STATUS_PICKED,
                ];
                break;
            case HotaiOrderStatus::STATUS_SHIPPING:
                $needStatues = [
                    HotaiOrderStatus::STATUS_PROCESSING,
                    HotaiOrderStatus::STATUS_TALLYING,
                    HotaiOrderStatus::STATUS_ARRIVED,
                    HotaiOrderStatus::STATUS_PICKED,
                    HotaiOrderStatus::STATUS_COMPLETE
                ];
                break;
            case HotaiOrderStatus::STATUS_TALLYING:
                $needStatues = [
                    HotaiOrderStatus::STATUS_PROCESSING,
                    HotaiOrderStatus::STATUS_SHIPPING,
                    HotaiOrderStatus::STATUS_ARRIVED,
                    HotaiOrderStatus::STATUS_PICKED,
                    HotaiOrderStatus::STATUS_COMPLETE
                ];
                break;
            case HotaiOrderStatus::STATUS_ARRIVED:
                $needStatues = [
                    HotaiOrderStatus::STATUS_FAILED_DELIVERY,
                    HotaiOrderStatus::STATUS_TALLYING,
                    HotaiOrderStatus::STATUS_COMPLETE
                ];
                break;
            case HotaiOrderStatus::STATUS_PICKED:
                $needStatues = [
                    HotaiOrderStatus::STATUS_COMPLETE
                ];
                break;
        }
        return $needStatues;
    }

    /**
     * @return array
     */
    public static function shippingStatues()
    {
        return [
            HotaiOrderStatus::STATUS_TALLYING,
            HotaiOrderStatus::STATUS_SHIPPING,
        ];
    }
}
