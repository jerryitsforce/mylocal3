<?php

namespace Branch8\CTBC\Helper;

use Branch8\CTBC\Model\Api;
use Branch8\CTBC\Model\OrderManagement;
use Branch8\CTBC\Helper\Log as CtbcLog;

class OrderStatus
{
    private const LOG_CLASS = 'OrderStatus';

    /** @var \Branch8\CTBC\Model\Api $api */
    protected $api;

    /** @var \Branch8\CTBC\Model\OrderManagement $orderManagement */
    protected $orderManagement;

    /** @var CtbcLog */
    protected CtbcLog $ctbcLog;

    /**
     * OrderStatus constructor.
     *
     * @param Api $api CTBC API client.
     * @param OrderManagement $orderManagement CTBC order management service.
     * @param CtbcLog $ctbcLog CTBC log facade.
     */
    public function __construct(
        Api $api,
        OrderManagement $orderManagement,
        CtbcLog $ctbcLog
    ) {
        $this->api = $api;
        $this->orderManagement = $orderManagement;
        $this->ctbcLog = $ctbcLog;
    }

    /**
     * Get CTBC refund/status data by order id.
     *
     * @param mixed $orderId Order entity id.
     * @return array<mixed> CTBC inquiry result, or empty array on exception.
     */
    public function getRefundDataByOrderId($orderId)
    {
        try {
            $parentOrder = $this->orderManagement->getParentOrder(true, $orderId);
            $this->api->setOrderInfo($parentOrder);
            return $this->orderManagement->inquiryOrderStatus($parentOrder);

        } catch (\Exception $e) {
            $this->ctbcLog->write(
                sprintf(
                    '[Exception] %s | %s:%d | orderId=%s',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    (string) $orderId
                ),
                self::LOG_CLASS
            );
            return [];
        }
    }

}
