<?php

namespace Branch8\Marketplace\Override\MpBundleProduct\Observer;

use Branch8\SellerContactInformation\Helper\Data as SellerHelper;

class SalesOrderPlaceAfter extends \Webkul\MpBundleProduct\Observer\SalesOrderPlaceAfter
{
    public function orderPlacedOperations($order, $lastOrderId){

        $this->productSalesCalculation($order);

        /*send placed order mail notification to the seller*/

        $paymentCode = '';
        if ($order->getPayment()) {
            $paymentCode = $order->getPayment()->getMethod();
        }

        $shippingInfo = '';
        $shippingDes = '';

        $billingId = $order->getBillingAddress()->getId();

        $billaddress = $this->orderAddressFactory->create()->load($billingId);
        $billinginfo = $billaddress['firstname'].'<br/>'.
            $billaddress['street'].'<br/>'.
            $billaddress['city'].' '.
            $billaddress['region'].' '.
            $billaddress['postcode'].'<br/>'.
            $this->countryModel->create()->load($billaddress['country_id'])->getName().'<br/>T:'.
            $billaddress['telephone'];

        $order->setOrderApprovalStatus(1)->save();

        $payment = $order->getPayment()->getMethodInstance()->getTitle();

        if ($order->getShippingAddress()) {
            $shippingId = $order->getShippingAddress()->getId();
            $address = $this->orderAddressFactory->create()->load($shippingId);
            $shippingInfo = $address['firstname'].'<br/>'.
                $address['street'].'<br/>'.
                $address['city'].' '.
                $address['region'].' '.
                $address['postcode'].'<br/>'.
                $this->countryModel->create()->load($address['country_id'])->getName().'<br/>T:'.
                $address['telephone'];
            $shippingDes = $order->getShippingDescription();
        }

        $adminStoremail = $this->_marketplaceHelper->getAdminEmailId();
        $defaultTransEmailId = $this->_marketplaceHelper->getDefaultTransEmailId();
        $adminEmail = $adminStoremail ?: $defaultTransEmailId;
        $adminUsername = $this->_marketplaceHelper->getAdminName();

        $sellerOrder = $this->ordersFactory->create()
            ->getCollection()
            ->addFieldToFilter('order_id', $lastOrderId)
            ->addFieldToFilter('seller_id', ['neq' => 0]);

        $sellerHelper = $this->_objectManager->get(SellerHelper::class);

        foreach ($sellerOrder as $info) {
            $userdata = $this->_customerRepository->getById($info['seller_id']);
            $sellerData = $sellerHelper->getSellerData($info['seller_id']);
            $sellerDataEnableLowNotification = (boolean)$sellerData['enable_low_notification'];

            $username = $userdata->getFirstname();
            $useremail = $userdata->getEmail();

            $receiverInfo = [
                'name' => $username,
                'email' => $useremail,
            ];
            $senderInfo = [
                'name' => $adminUsername,
                'email' => $adminEmail,
            ];
            $totalprice = 0;
            $totalTaxAmount = 0;
            $codCharges = 0;
            $shippingCharges = 0;
            $orderinfo = '';

            $collection1 = $this->saleslistFactory->create()
                ->getCollection()
                ->addFieldToFilter('order_id', $lastOrderId)
                ->addFieldToFilter('seller_id', $info['seller_id'])
                ->addFieldToFilter('parent_item_id', ['null' => 'true'])
                ->addFieldToFilter('magerealorder_id', ['neq' => 0])
                ->addFieldToSelect('entity_id');

            $saleslistIds = $collection1->getData();

            $fetchsale = $this->saleslistFactory->create()
                ->getCollection()
                ->addFieldToFilter(
                    'entity_id',
                    ['in' => $saleslistIds]
                );
            $fetchsale->getSellerOrderCollection();
            foreach ($fetchsale as $res) {
                $product = $this->_productRepository->getById($res['mageproduct_id']);

                /* product name */
                $productName = $res->getMageproName();
                $result = [];
                $result = $this->getProductOptionData($res, $result);
                $productName = $this->getProductNameHtml($result, $productName);
                /* end */
                if ($res->getProductType() == 'configurable') {
                    $configurableSalesItem = $this->saleslistFactory->create()
                        ->getCollection()
                        ->addFieldToFilter('order_id', $lastOrderId)
                        ->addFieldToFilter('seller_id', $info['seller_id'])
                        ->addFieldToFilter('parent_item_id', $res->getOrderItemId());
                    $configurableItemArr = $configurableSalesItem->getOrderedProductId();
                    $configurableItemId = $res['mageproduct_id'];
                    if (!empty($configurableItemArr)) {
                        $configurableItemId = $configurableItemArr[0];
                    }
                    $product = $this->_productRepository->getById($configurableItemId);
                } else if($res->getProductType() == 'bundle'){
                    /**
                     * Fobundle product we have to check and notify low stock for child products
                     */
                    $bundleSalesItem = $this->saleslistFactory->create()
                        ->getCollection()
                        ->addFieldToFilter('order_id', $lastOrderId)
                        ->addFieldToFilter('seller_id', $info['seller_id'])
                        ->addFieldToFilter('parent_item_id', $res->getOrderItemId());
                    $bundleItemArr = $bundleSalesItem->getOrderedProductId();
                    $productBundle = [];
                    foreach($bundleItemArr as $_bundleItem){
                        $productBundle[$_bundleItem] = $this->_productRepository->getById($_bundleItem);
                    }
                } else{
                    $product = $this->_productRepository->getById($res['mageproduct_id']);
                }

                $sku = $product->getSku();
                $pid = $product->getId();
                $orderinfo = $orderinfo."<tbody><tr>
                                <td class='item-info'>".$productName."</td>
                                <td class='item-info'>".$sku."</td>
                                <td class='item-qty'>".($res['magequantity'] * 1)."</td>
                                <td class='item-price'>".
                    $order->formatPrice(
                        $res['magepro_price'] * $res['magequantity']
                    ).
                    '</td>
                             </tr></tbody>';
                $totalTaxAmount = $totalTaxAmount + $res['total_tax'];
                $totalprice = $totalprice + ($res['magepro_price'] * $res['magequantity']);

                /*
                * Low Stock Notification mail to seller
                * Check seller notification
                */
                if (
                    $sellerDataEnableLowNotification === false &&
                    $this->_marketplaceHelper->getlowStockNotification()
                ) {
                    /**
                     * Update logic notifies stock for Bundle product, need to check & notify for all childs
                     */
                    if($product->getTypeId() != 'bundle'){
                        $skus = [$pid => $sku];
                    }else{
                        foreach($productBundle as $pBundle){
                            $skus[$pBundle->getId()] = $pBundle->getSku();
                        }
                    }
                    foreach($skus as $pid => $sku){
                        $product = $this->_productRepository->getById($pid);
                        $lowStockProductName = $product->getName();
                        $stockState = $this->_objectManager
                            ->get(\Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku::class);
                        $salable = $stockState->execute($sku);
                        if (isset($salable[0]['qty'])) {
                            $stockItemQty = $salable[0]['qty'];
                        } else {
                            $productData = $product->getData();
                            if (!empty($productData['quantity_and_stock_status']['qty'])) {
                                $stockItemQty = $productData['quantity_and_stock_status']['qty'];
                            }else{
                                $stockItemQty = $product->getQty();
                            }
                        }

                        if ($stockItemQty <= $this->_marketplaceHelper->getlowStockQty()) {
                            $orderProductInfo = "<tbody><tr>
                                <td class='item-info'>".$lowStockProductName."</td>
                                <td class='item-info'>".$sku."</td>
                                <td class='item-qty'>".($stockItemQty * 1).'</td>
                             </tr></tbody>';

                            $emailTemplateVariables = [];
                            $emailTemplateVariables['myvar1'] = $orderProductInfo;
                            $emailTemplateVariables['myvar2'] = $username;
                            $emailTemplateVariables['product_name'] = $lowStockProductName;
                            $emailTemplateVariables['specification_name'] = $productName;
                            $emailTemplateVariables['sku'] = $sku;

                            $this->mpEmailHelper->sendLowStockNotificationMail(
                                $emailTemplateVariables,
                                $senderInfo,
                                $receiverInfo
                            );
                        }
                    }

                }
            }
            $shippingCharges = $info->getShippingCharges();
            $couponAmount = $info->getCouponAmount();
            $totalCod = 0;

            if ($paymentCode == 'mpcashondelivery') {
                $totalCod = $info->getCodCharges();
                $codRow = "<tr class='subtotal'>
                            <th colspan='3'>".__('Cash On Delivery Charges')."</th>
                            <td colspan='3'><span>".
                    $order->formatPrice($totalCod).
                    '</span></td>
                            </tr>';
            } else {
                $codRow = '';
            }

            $orderinfo = $orderinfo."<tfoot class='order-totals'>
                                <tr class='subtotal'>
                                    <th colspan='3'>".__('Shipping & Handling Charges')."</th>
                                    <td colspan='3'><span>".
                $order->formatPrice($shippingCharges)."</span></td>
                                </tr>
                                <tr class='subtotal'>
                                    <th colspan='3'>".__('Discount')."</th>
                                    <td colspan='3'><span> -".
                $order->formatPrice($couponAmount).
                "</span></td>
                                </tr>
                                <tr class='subtotal'>
                                    <th colspan='3'>".__('Tax Amount')."</th>
                                    <td colspan='3'><span>".
                $order->formatPrice($totalTaxAmount).'</span></td>
                                </tr>'.$codRow."
                                <tr class='subtotal'>
                                    <th colspan='3'>".__('Grandtotal')."</th>
                                    <td colspan='3'><span>".
                $order->formatPrice(
                    $totalprice +
                    $totalTaxAmount +
                    $shippingCharges +
                    $totalCod -
                    $couponAmount
                ).'</span></td>
                                </tr></tfoot>';

            $emailTemplateVariables = [];
            if ($shippingInfo != '') {
                $isNotVirtual = 1;
            } else {
                $isNotVirtual = 0;
            }
            $emailTempVariables['myvar1'] = $order->getRealOrderId();
            $emailTempVariables['myvar2'] = $order['created_at'];
            $emailTempVariables['myvar4'] = $billinginfo;
            $emailTempVariables['myvar5'] = $payment;
            $emailTempVariables['myvar6'] = $shippingInfo;
            $emailTempVariables['isNotVirtual'] = $isNotVirtual;
            $emailTempVariables['myvar9'] = $shippingDes;
            $emailTempVariables['myvar8'] = $orderinfo;
            $emailTempVariables['myvar3'] = $username;

            if ($this->_marketplaceHelper->getOrderApprovalRequired()) {
                $emailTempVariables['seller_id'] = $info['seller_id'];
                $emailTempVariables['order_id'] = $lastOrderId;
                $emailTempVariables['sender_name'] = $senderInfo['name'];
                $emailTempVariables['sender_email'] = $senderInfo['email'];
                $emailTempVariables['receiver_name'] = $receiverInfo['name'];
                $emailTempVariables['receiver_email'] = $receiverInfo['email'];

                $orderPendingMailsCollection = $this->orderPendingMailsFactory->create();
                $orderPendingMailsCollection->setData($emailTempVariables);
                $orderPendingMailsCollection->setCreatedAt($this->_date->gmtDate());
                $orderPendingMailsCollection->setUpdatedAt($this->_date->gmtDate());
                $orderPendingMailsCollection->save();
                $order->setOrderApprovalStatus(0)->save();
            } else {
                $this->mpEmailHelper->sendPlacedOrderEmail(
                    $emailTempVariables,
                    $senderInfo,
                    $receiverInfo
                );
            }
        }
    }
}
