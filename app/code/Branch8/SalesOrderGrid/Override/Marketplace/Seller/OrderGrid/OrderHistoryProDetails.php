<?php

namespace Branch8\SalesOrderGrid\Override\Marketplace\Seller\OrderGrid;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Sales\Model\OrderRepository;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory;

class OrderHistoryProDetails extends \Webkul\Marketplace\Ui\Component\Listing\Columns\Frontend\OrderHistoryProDetails
{

    protected $itemCollectionFactory;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        HelperData $helper,
        CollectionFactory $collectionFactory,
        ItemRepository $orderItemRepository,
        OrderRepository $orderRepository,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $itemCollectionFactory,
        array $components = [],
        array $data = []
    ) {

        parent::__construct($context, $uiComponentFactory, $helper, $collectionFactory,
            $orderItemRepository, $orderRepository, $components, $data);
        $this->itemCollectionFactory = $itemCollectionFactory;
    }

    public function prepareDataSource(array $dataSource)
    {
        $taxToSeller = $this->_helper->getConfigTaxManage();
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                // calculate order actual_seller_amount in base currency
                $appliedCouponAmount = $item['applied_coupon_amount'];
                $taxToSeller = $item['tax_to_seller'];
                $shippingamount = $item['shipping_charges'];
                $refundedShippingAmount = $item['refunded_shipping_charges'];
                $totalshipping = $shippingamount - $refundedShippingAmount;
                $taxAmount = $item['total_tax'];
                $vendorTaxAmount = 0;
                if ($taxToSeller) {
                    $vendorTaxAmount = $taxAmount;
                }
                if ($item['actual_seller_amount'] * 1) {
                    $taxShippingTotal = $vendorTaxAmount + $totalshipping;
                    $item['actual_seller_amount'] = $item['actual_seller_amount'] + $taxShippingTotal;
                } else {
                    if ($totalshipping * 1) {
                        $item['actual_seller_amount'] = $totalshipping;
                    }
                }
                // calculate order total in ordered currency
//                $order = $this->orderRepository->get($item['order_id']);

                $item['purchased_actual_seller_amount'] = $item['currency_rate'] * $item['actual_seller_amount'];

                // Updated product name
                $item['magepro_name'] = $this->getProNameByOrderCustom(
                    $item['order_item_ids'] ?? ''
                );
            }
        }

        return $dataSource;
    }

    /**
     * Get product name by order
     *
     * @param string $itemIds
     * @return string
     */
    public function getProNameByOrderCustom($itemIds)
    {
        if(!$itemIds){
            return '';
        }
        $items = $this->itemCollectionFactory->create()
            ->addFieldToFilter('parent_item_id', ['null' => true])
            ->addFieldToFilter('item_id', ['in' => explode(',', $itemIds)]);

        $productName = '';
        foreach ($items as $item) {
            $url = '';
            // Updated product name
            $result = [];
            if ($options = $item['product_options']) {
                if (isset($options['options'])) {
                    $result = $this->getMergedArray($result, $options['options']);
                }
                if (isset($options['additional_options'])) {
                    $result = $this->getMergedArray($result, $options['additional_options']);
                }
                if (isset($options['attributes_info'])) {
                    $result = $this->getMergedArray($result, $options['attributes_info']);
                }
            }
            if ($item->getProduct() && $item->getProduct()->getVisibility()!=1) {
                $url = $item->getProduct()->getProductUrl();
                $productName = $productName."<a href='".$url."' target='blank'>".$item['name']."</a>";
            } else {
                $productName = $productName.$item['name'];
            }
            $productName = $this->getProductNameHtml($result, $productName);
            /*prepare product quantity status*/
            $isForItemPay = 0;
            if ($item['qty_ordered'] > 0) {
                $productName = $productName.__('Ordered').
                    ': <strong>'.($item['qty_ordered'] * 1).'</strong><br />';
            }
            if ($item['qty_invoiced'] > 0) {
                ++$isForItemPay;
                $productName = $productName.
                    __('Invoiced').
                    ': <strong>'.
                    ($item['qty_invoiced'] * 1).
                    '</strong><br />';
            }
            if ($item['qty_shipped'] > 0) {
                ++$isForItemPay;
                $productName = $productName.__('Shipped').
                    ': <strong>'.($item['qty_shipped'] * 1).'</strong><br />';
            }
            if ($item['qty_canceled'] > 0) {
                $isForItemPay = 4;
                $productName = $productName.
                    __('Canceled').
                    ': <strong>'.
                    ($item['qty_canceled'] * 1).
                    '</strong><br />';
            }
            if ($item['qty_refunded'] > 0) {
                $isForItemPay = 3;
                $productName = $productName.
                    __('Refunded').
                    ': <strong>'.
                    ($item['qty_refunded'] * 1).
                    '</strong><br />';
            }
        }

        return $productName;
    }
}
