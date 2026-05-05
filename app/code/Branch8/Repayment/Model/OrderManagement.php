<?php

namespace Branch8\Repayment\Model;

use \Magento\Checkout\Model\Session;
use \Magento\Framework\Session\SessionManager;
use \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory as ParentOrderCollection;
use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use \Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Branch8\Repayment\Helper\Log as RepaymentLog;

class OrderManagement
{
    /**
     * Checkout session key for last success quote id.
     */
    const LAST_SUCCESS_QUOTE_ID = 'LastSuccessQuoteId';

    /**
     * Checkout session key for last quote id.
     */
    const LAST_QUOTE_ID = 'LastQuoteId';

    /**
     * Checkout session key for last order id.
     */
    const LAST_ORDER_ID = 'LastOrderId';

    /**
     * Checkout session key for last increment id.
     */
    const LAST_REAL_ORDER_ID = 'LastRealOrderId';

    /**
     * Checkout session key for grand total.
     */
    const GRAND_TOTAL = 'grand_total';

    /**
     * Checkout session key for sub-order id list.
     */
    const SUB_ORDERS = 'sub_orders';
    protected $checkoutSession;
    protected $coreSession;
    protected $parentOrderCollection;
    protected $parentOrderManagement;
    protected $parentOrder;
    protected $orderFactory;
    protected $customerSession;
    protected $dateTime;
    protected $repaymentLog;

    /**
     * Initialize order management dependencies.
     *
     * @param SessionManager $coreSession Core session manager.
     * @param Session $checkoutSession Checkout session.
     * @param ParentOrderManagementInterface $parentOrderManagement Parent order service.
     * @param ParentOrderCollection $parentOrderCollection Parent order collection factory.
     * @param OrderInterfaceFactory $orderFactory Sales order factory.
     * @param ParentOrder $parentOrder Parent order resource.
     * @param CustomerSession $customerSession Customer session.
     * @param DateTime $dateTime Date time helper.
     * @param RepaymentLog $repaymentLog Repayment log helper.
     */
    public function __construct(
        SessionManager $coreSession,
        Session $checkoutSession,
        ParentOrderManagementInterface $parentOrderManagement,
        ParentOrderCollection $parentOrderCollection,
        OrderInterfaceFactory $orderFactory,
        ParentOrder $parentOrder,
        CustomerSession $customerSession,
        DateTime $dateTime,
        RepaymentLog $repaymentLog
    ) {

        $this->checkoutSession = $checkoutSession;
        $this->coreSession = $coreSession;
        $this->parentOrderCollection = $parentOrderCollection;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->parentOrder = $parentOrder;
        $this->orderFactory = $orderFactory;
        $this->customerSession = $customerSession;
        $this->dateTime = $dateTime;
        $this->repaymentLog = $repaymentLog;
    }

    /**
     * Store parent order id into core session.
     *
     * @param int|string $parentId Parent order id.
     */
    public function setParentOrderId($parentId)
    {
        $this->coreSession->setData('parentOrderId', $parentId);
    }

    /**
     * Get parent orders available for current customer and status filter.
     *
     * @param int|string|array<int|string> $parentOrderId Parent order id list.
     * @return \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\Collection
     */
    public function getOrders($parentOrderId)
    {

        $collection = $this->parentOrderCollection->create()
            ->joinDetail(
                [
                    'increment_id' => 'increment_id',
                    'status' => 'status',
                    'created_at' => 'created_at',
                    'customer_id' => 'customer_id'
                ]
            )->addCustomerFilter(
                $this->customerSession->getCustomerId()
            )->addFieldToFilter(
                'detail.status',
                ['in' => ['pending', 'pending_payment']]
            )->addFieldToFilter(
                'detail.parent_id',
                ['in' => $parentOrderId]
            );

        return $collection;
    }

    /**
     * Read grand total from parent order totals.
     *
     * @param ParentOrderInterface $parentOrder Parent order entity.
     * @return int
     */
    public function getGrandTotal(ParentOrderInterface $parentOrder)
    {
        try {
            $totals = $this->parentOrderManagement->getTotals($parentOrder);
            foreach ($totals as $total) {
                if ($total->getCode() === 'grand_total') {
                    return (int) $total->getValue();
                }
            }
            return 0;
        } catch (\Exception $exception) {
            $this->repaymentLog->exception(
                $exception,
                $this->repaymentLog->orderManagementOption(),
                __METHOD__,
                ['parent_order_id' => $parentOrder->getParentId()]
            );
            return 0;
        }
    }



    /**
     * Read total value by total code from parent order totals.
     *
     * @param ParentOrderInterface $parentOrder Parent order entity.
     * @param string $code Total code.
     * @return int
     */
    public function getTotalByCode(ParentOrderInterface $parentOrder, $code = 'grand_total')
    {
        try {
            $totals = $this->parentOrderManagement->getTotals($parentOrder);
            foreach ($totals as $total) {
                if ($total->getCode() === $code) {
                    return (int) $total->getValue();
                }
            }
            return 0;
        } catch (\Exception $exception) {
            $this->repaymentLog->exception(
                $exception,
                $this->repaymentLog->orderManagementOption(),
                __METHOD__,
                [
                    'parent_order_id' => $parentOrder->getParentId(),
                    'total_code' => $code,
                ]
            );
            return 0;
        }
    }

    /**
     * Get linked sub-orders by parent id.
     *
     * @param int|string $parentId Parent order id.
     * @return array
     */
    public function getSubOrders($parentId)
    {
        return $this->parentOrder->getSubOrders((int) $parentId);
    }

    /**
     * Update payment information for all child orders.
     *
     * @param int|string $parentOrderId Parent order id.
     * @param string $paymentMethod Payment method code.
     * @param object $ccData Credit card data object.
     * @return array<string, mixed>
     */
    public function updateOrderPaymentInfo($parentOrderId, $paymentMethod, $ccData)
    {
        $subOrders = $this->getSubOrders($parentOrderId);
        $ccLast4 = (isset($ccData->CardNoMask)) ? substr($ccData->CardNoMask, -4) : '';
        $orderIds = [];

        foreach ($subOrders as $order) {
            $lastOrderId = $order['children_id'];
            $orderIds[] = $lastOrderId;

            $order = $this->orderFactory->create()->load($order['children_id']);
            $lastIncrementId = $order->getIncrementId();
            $quoteId = $order->getQuoteId();
            $payment = $order->getPayment();

            $payment
                ->setMethod($paymentMethod)
                ->setCcTokenId($ccData->Id)
                ->setCcType($ccData->CardType)
                ->setCcOwner($ccData->MemberOneID)
                ->setCcLast4($ccLast4)
                ->save();

            $order->save();
        }

        $orderCheckoutData = [
            self::LAST_SUCCESS_QUOTE_ID => $quoteId,
            self::LAST_QUOTE_ID => $quoteId,
            self::LAST_ORDER_ID =>  $lastOrderId,
            self::LAST_REAL_ORDER_ID => $lastIncrementId,
            self::GRAND_TOTAL => $this->getGrandTotalByParentOrderId($parentOrderId),
            self::SUB_ORDERS => $orderIds
        ];

        return $orderCheckoutData;
    }

    /**
     * Calculate grand total by parent order id.
     *
     * @param int|string $parentOrderId Parent order id.
     * @return int|null
     */
    private function getGrandTotalByParentOrderId($parentOrderId)
    {
        $parentOrder  = $this->getOrders($parentOrderId);
        foreach ($parentOrder as $order) {
            return $this->getGrandTotal($order);
        }
    }
}
