<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceStaging\Plugin\Webkul\Marketplace\Helper;

use Webkul\Marketplace\Model\OrdersFactory as MpOrdersFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Model\OrderRepository;

class Orders
{
    /**
     * @var array
     */
    protected $listOrder = [];

    /**
     * @var MpOrdersFactory
     */
    protected $mpOrdersFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    protected $subAccountHelper;

    /**
     * @param MpOrdersFactory $mpOrdersFactory
     * @param CustomerSession $customerSession
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        MpOrdersFactory $mpOrdersFactory,
        CustomerSession $customerSession,
        OrderRepository $orderRepository,
        \Webkul\SellerSubAccount\Helper\Data $subAccountHelper
    ) {
        $this->mpOrdersFactory = $mpOrdersFactory;
        $this->customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
        $this->subAccountHelper = $subAccountHelper;
    }

    /**
     * Return the seller Order data.
     *
     * @param int $orderId
     * @return \Webkul\Marketplace\Api\Data\OrdersInterface
     */
    public function aroundGetOrderinfo(
        \Webkul\Marketplace\Helper\Orders $subject,
        \Closure $proceed,
        $orderId = ''
    ) {
        if ($orderId) {
            if (isset($this->listOrder[$orderId])) {
                return $this->listOrder[$orderId];
            }
            $sellerId = $this->customerSession->getCustomerId();
            $subAccount = $this->subAccountHelper->getCurrentSubAccount();
            if ($subAccount->getId()) {
                $sellerId = $subAccount->getSellerId();
            }
            $model = $this->mpOrdersFactory->create()
                ->getCollection()
                ->addFieldToFilter(
                    'seller_id',
                    $sellerId
                )
                ->addFieldToFilter(
                    'order_id',
                    $orderId
                );

            $salesOrder = $this->mpOrdersFactory->create()->getCollection()->getTable('sales_order');

            $model->getSelect()->join(
                $salesOrder . ' as so',
                'main_table.order_id = so.entity_id',
                ["order_approval_status" => "order_approval_status"]
            )->where("so.order_approval_status=1");
            $tracking = $model->getFirstItem();
            $this->listOrder[$orderId] = false;
            if ($tracking->getId()) {
                $order = $this->orderRepository->get($orderId);
                $invoiceCollection = $order->getInvoiceCollection();
                $shipmentCollection = $order->getShipmentsCollection();
                $creditmemoCollection = $order->getCreditmemosCollection();
                $invoiceIds = '';
                $shipmentIds = '';
                $creditmemoIds = '';
                foreach ($invoiceCollection as $invoice) {
                    $invoiceIds = ($invoiceIds) ? $invoiceIds . ',' . $invoice->getId() : $invoice->getId();
                }
                foreach ($shipmentCollection as $shipment) {
                    $shipmentIds = ($shipmentIds) ? $shipmentIds . ',' . $shipment->getId() : $shipment->getId();
                }
                foreach ($creditmemoCollection as $creditmemo) {
                    $creditmemoIds = ($creditmemoIds) ? $creditmemoIds . ',' . $creditmemo->getId() : $creditmemo->getId();
                }
                if ($invoiceIds) {
                    $tracking->setInvoiceId($invoiceIds);
                }
                if ($shipmentIds) {
                    $tracking->setTrackingNumber(1);
                    $tracking->setShipmentId($shipmentIds);
                }
                if ($creditmemoIds) {
                    $tracking->setCreditmemoId($creditmemoIds);
                }
                $tracking->setOrderStatus($order->getStatus());
                $this->listOrder[$orderId] = $tracking;
            }
            return $this->listOrder[$orderId];
        }

        return false;
    }
}
