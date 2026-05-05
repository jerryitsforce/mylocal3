<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceStaging\Plugin\Webkul\Marketplace\Block\Order;

use Webkul\Marketplace\Model\OrdersFactory as MpOrderModel;

class View
{
    /**
     * @var array
     */
    protected $_links = [];

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var MpOrderModel
     */
    protected $mpOrderModel;

    /**
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param MpOrderModel $mpOrderModel
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        MpOrderModel $mpOrderModel
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->mpOrderModel = $mpOrderModel;
    }

    /**
     * Get links
     *
     * @return void
     */
    public function aroundGetLinks(
        \Webkul\Marketplace\Block\Order\View $subject,
        \Closure $proceed
    ){
        $this->checkLinks($subject);

        return $this->_links;
    }

    /**
     * Get seller order info
     *
     * @param string $orderId
     * @return array
     */
    public function aroundGetSellerOrderInfo(
        \Webkul\Marketplace\Block\Order\View $subject,
        \Closure $proceed,
        $orderId = ''
    ){
        $collection = $this->mpOrderModel->create()->getCollection()
            ->addFieldToFilter(
                'order_id',
                ['eq' => $orderId]
            )
            ->addFieldToFilter(
                'seller_id',
                ['eq' => $subject->getCustomerId()]
            );
        $collection->getSelect()->join(
            ['order' => $collection->getTable('sales_order')],
            'main_table.order_id = order.entity_id',
            ['order_status' => 'order.status']
        );

        return $collection;
    }

    /**
     * Check links
     *
     * @return void
     */
    private function checkLinks($subject)
    {
        $order = $subject->getOrder();
        $orderId = $order->getId();
        $this->_links['order'] = [
            'name' => 'order',
            'label' => __('Items Ordered'),
            'url' => $this->_urlBuilder->getUrl(
                'marketplace/order/view',
                [
                    'id' => $orderId,
                    '_secure' => $subject->getRequest()->isSecure()
                ]
            ),
        ];
        if (!$order->hasInvoices()) {
            unset($this->_links['invoice']);
        } else {
            $this->_links['invoice'] = [
                'name' => 'invoice',
                'label' => __('Invoices'),
                'url' => $this->_urlBuilder->getUrl(
                    'marketplace/order_invoice/viewlist',
                    [
                        'order_id' => $orderId,
                        '_secure' => $subject->getRequest()->isSecure()
                    ]
                ),
            ];
        }
        if (!$order->hasShipments()) {
            unset($this->_links['shipment']);
        } else {
            $this->_links['shipment'] = [
                'name' => 'shipment',
                'label' => __('Shipments'),
                'url' => $this->_urlBuilder->getUrl(
                    'marketplace/order_shipment/viewlist',
                    [
                        'order_id' => $orderId,
                        '_secure' => $subject->getRequest()->isSecure()
                    ]
                ),
            ];
        }

        if (!$order->hasCreditmemos()) {
            unset($this->_links['creditmemo']);
        } else {
            $this->_links['creditmemo'] = [
                'name' => 'creditmemo',
                'label' => __('Refunds'),
                'url' => $this->_urlBuilder->getUrl(
                    'marketplace/order_creditmemo/viewlist',
                    [
                        'order_id' => $orderId,
                        '_secure' => $subject->getRequest()->isSecure()
                    ]
                ),
            ];
        }
    }
}
