<?php

namespace Branch8\Sales\Plugin\Webkul\Marketplace\Helper;

use Magento\Sales\Model\Order;
use Webkul\Marketplace\Model\OrdersFactory as MpOrdersFactory;

class OrdersPlugin
{
    /**
     * @var MpOrdersFactory
     */
    protected $mpOrdersFactory;

    public function __construct(MpOrdersFactory $mpOrdersFactory)
    {
        $this->mpOrdersFactory = $mpOrdersFactory;
    }

    /**
     * @param $subject
     * @param Callable $process
     * @param Order $order
     * @param $sellerId
     * @param $comment
     * @return int
     */
    public function aroundMpregisterCancellation($subject, \Closure $process, $order, $sellerId, $comment = '')
    {
        /**
         * Use default function from Magento ,Seem webkull not process logic cancelation
         * https://trello.com/c/n530oHqM/770-bug-admin-order-stock-is-not-increase-after-admin-cancel-order
         */
        $flag = false;
        if ($order->canCancel()) {
            $order->registerCancellation();
            $flag = true;
        }
        return $flag;
    }
}
