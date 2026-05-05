<?php

namespace Branch8\Marketplace\Override\Webkul\Marketplace\Block\Order;

use Magento\Sales\Model\OrderFactory;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\Collection;

class Totals extends \Webkul\Marketplace\Block\Order\Totals
{
    private $orderFactory;

    /**
     * @param \Webkul\Marketplace\Helper\Data $helper
     * @param \Magento\Framework\Registry $coreRegistry
     * @param Collection $orderCollection
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Webkul\Marketplace\Helper\Data                  $helper,
        \Magento\Framework\Registry                      $coreRegistry,
        Collection                                       $orderCollection,
        OrderFactory                                     $orderFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        array                                            $data = []
    )
    {
        parent::__construct(
            $helper,
            $coreRegistry,
            $orderCollection,
            $context,
            $data
        );
        $this->orderFactory = $orderFactory;
    }

    protected function _initTotals()
    {
        $this->_totals = [];
        $source = $this->getSource();
        $order = $this->getOrder();
        $isFlagshipStoreProcessOrder = $order->getData('is_flagship_store_process_order');
        if (isset($source[0])) {
            $source = $source[0];
            $taxToSeller = $source['tax_to_seller'];
            $currencyRate = $source['currency_rate'];
            $subtotal = $source['magepro_price'];
            $adminSubtotal = $source['total_commission'];
            $shippingamount = $source['shipping_charges'];
            $refundedShippingAmount = $source['refunded_shipping_charges'];
            $couponAmount = $source['applied_coupon_amount'];
            $totaltax = $source['total_tax'];
            $totalCouponAmount = $source['coupon_amount'];

            $admintotaltax = 0;
            $vendortotaltax = 0;
            if (!$taxToSeller) {
                $admintotaltax = $totaltax;
            } else {
                $vendortotaltax = $totaltax;
            }
            $pointDiscountTotal = $order->getData('point_discount_total');
            $totalOrdered = $this->getOrderedAmount($source);

            $vendorSubTotal = $this->getVendorSubTotal($source);

            $adminSubTotal = $this->getAdminSubTotal($source);

            $this->_totals = [];
            $subtotal = $order->getSubtotalInclTax();
            $shippingAmount = $order->getShippingInclTax();
            if($isFlagshipStoreProcessOrder){
                $shippingAmount = $subtotal;
                $subtotal = 0;
            }
            $this->_totals['subtotal'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'subtotal',
                    'value' => $subtotal,
                    'label' => __('Subtotal')
                ]
            );

            $this->_totals['shipping'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'shipping',
                    'value' => $shippingAmount,
                    'label' => __('Shipping & Handling')
                ]
            );

            $this->_totals['discount'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'discount',
                    'value' => $order->getDiscountAmount(),
                    'label' => __('Discount')
                ]
            );

            $this->_totals['tax'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'tax',
                    'value' => $order->getTaxAmount(),
                    'label' => __('Total Tax')
                ]
            );

            $this->_totals['point'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'point',
                    'value' => $this->helper->getCurrentCurrencyPrice($currencyRate, $pointDiscountTotal ? -$pointDiscountTotal : 0),
                    'label' => __('Point Discount')
                ]
            );


            $this->_totals['ordered_total'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'ordered_total',
                    'strong' => 1,
                    'value' => $order->getGrandTotal(),
                    'label' => __('Grand Total')
                ]
            );


            $this->_totals['total_paid'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'total_paid',
                    'strong' => 1,
                    'value' => $order->getTotalPaid(),
                    'label' => __('Total Paid')
                ]
            );

            $this->_totals['total_refunded'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'total_refunded',
                    'strong' => 1,
                    'value' => $order->getTotalRefunded(),
                    'label' => __('Total Refunded')
                ]
            );

            $this->_totals['total_due'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'total_due',
                    'strong' => 1,
                    'value' =>$order->getTotalDue(),
                    'label' => __('Total Due')
                ]
            );
            // temporary hide
            /*  $this->_totals['vendor_total'] = new \Magento\Framework\DataObject(
                  [
                      'code' => 'vendor_total',
                      'value' => $this->helper->getCurrentCurrencyPrice($currencyRate, $vendorSubTotal),
                      'label' => __('Total Vendor Amount')
                  ]
              );
              $this->_totals['admin_commission'] = new \Magento\Framework\DataObject(
                  [
                      'code' => 'admin_commission',
                      'value' => $this->helper->getCurrentCurrencyPrice($currencyRate, $adminSubTotal),
                      'label' => __('Total Admin Commission')
                  ]
              );*/
        }

    }
}
