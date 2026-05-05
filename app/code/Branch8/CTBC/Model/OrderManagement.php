<?php

namespace Branch8\CTBC\Model;

use Branch8\CTBC\Model\Api;
use Branch8\CTBC\Helper\Log as CtbcLog;
use Branch8\HotaiPay\Model\ResourceModel\ParentOrderPayment\CollectionFactory as ParentOrderPaymentCollection;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;


class OrderManagement
{
    private const LOG_CLASS = 'OrderManagement';

    /** @var \Branch8\CTBC\Model\Api $ctbcApi */
    protected $ctbcApi;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder $parentOrder */
    protected $parentOrder;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory $parentOrderDetail */
    protected $parentOrderDetail;

    /** @var \Branch8\HotaiPay\Model\ResourceModel\ParentOrderPayment\CollectionFactory $parentOrderPaymentCollection */
    protected $parentOrderPaymentCollection;

    
    /** @var \Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface $parentOrderManagement */
    protected $parentOrderManagement;

    /** @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderInterface */
    protected $parentOrderInterface;

    /** @var CtbcLog */
    protected CtbcLog $ctbcLog;

    /**
     * OrderManagement constructor.
     *
     * @param Api $ctbcApi CTBC API client.
     * @param ParentOrder $parentOrder Parent order resource model.
     * @param ParentOrderDetailFactory $parentOrderDetail Parent order detail factory.
     * @param ParentOrderPaymentCollection $parentOrderPaymentCollection Parent order payment collection factory.
     * @param ParentOrderManagementInterface $parentOrderManagement Parent order management service.
     * @param ParentOrderFactory $parentOrderInterface Parent order model factory.
     * @param CtbcLog $ctbcLog CTBC log facade.
     */
    public function __construct(
        Api $ctbcApi,
        ParentOrder $parentOrder,
        ParentOrderDetailFactory $parentOrderDetail,
        ParentOrderPaymentCollection $parentOrderPaymentCollection,
        ParentOrderManagementInterface $parentOrderManagement,
        ParentOrderFactory $parentOrderInterface,
        CtbcLog $ctbcLog,
        
    ) {
        $this->ctbcApi = $ctbcApi;
        $this->parentOrder = $parentOrder;
        $this->parentOrderDetail = $parentOrderDetail;
        $this->parentOrderPaymentCollection = $parentOrderPaymentCollection;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->parentOrderInterface = $parentOrderInterface;
        $this->ctbcLog = $ctbcLog;
    }

    /**
     * Load parent order by provided order id.
     *
     * @param bool $isChildOrder Whether the provided id is a child order id.
     * @param int $orderId Order entity id.
     * @return ParentOrderDetail Loaded parent order detail model.
     */
    public function getParentOrder(bool $isChildOrder, int $orderId)
    {
        if ($isChildOrder) {
            $parentOrderId = $this->parentOrder->getParentOrder($orderId);
            $parentOrder = $this->parentOrderDetail->create()->load($parentOrderId, ParentOrderDetail::PARENT_ID);
        } else {
            $parentOrder = $this->parentOrderDetail->create()->load($orderId, ParentOrderDetail::PARENT_ID);
        }

        return $parentOrder;
    }

    /**
     * Query order status from CTBC.
     *
     * @param mixed $parentOrder Parent order detail model.
     * @return array<mixed>
     */
    public function inquiryOrderStatus($parentOrder)
    {
        $purchAmt = $this->getOrderPurchAmt($parentOrder);

        return $this->ctbcApi->inquiryOrder(
            [
                'LID-M' => $parentOrder->getIncrementId(),
                'purchAmt' => (int) $purchAmt,
            ]
        );
    }
    
    /**
     * Get order purchase amount for CTBC request.
     *
     * @param mixed $parentOrder Parent order detail model.
     * @return int Purchase amount (grand total) in integer.
     */
    public function getOrderPurchAmt($parentOrder)
    {
        $parentOrder = $this->parentOrderInterface->create()->load(
            $parentOrder->getParentId(), 'index_id');

        try {
            $totals = $this->parentOrderManagement->getTotals($parentOrder);
            foreach ($totals as $total) {
                if ($total->getCode() === 'grand_total') {
                    return (int) $total->getValue();
                }
            }
            return 0;
        } catch (\Exception $e) {
            $this->ctbcLog->write(
                sprintf(
                    '[Exception] %s | %s:%d | parentId=%s',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    method_exists($parentOrder, 'getParentId') ? (string) $parentOrder->getParentId() : ''
                ),
                self::LOG_CLASS
            );
            return 0;
        }
    }

}
