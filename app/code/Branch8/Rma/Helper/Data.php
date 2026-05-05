<?php

namespace Branch8\Rma\Helper;

use Branch8\Rma\Helper\Config\Shipping;
use Branch8\Rma\Helper\Config\StatusLabel;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Magento\Framework\App\ObjectManager;
use Webkul\MpRmaSystem\Model\Details;

class Data extends \Webkul\MpRmaSystem\Helper\Data
{
    /**
     * Log option value for this helper.
     */
    private const LOG_OPTION = 'Data';

    protected $rmaActions;

    /**
     * iniRmaStatus 初始化 RMA 狀態
     *
     * @param int $resolution
     * @return int
     */
    public function iniRmaStatus($resolution = 0)
    {
        switch ($resolution) {
            case self::RESOLUTION_REFUND:
                return RmaStatus::RETURN_APPLY_PROCESSING;
            case self::RESOLUTION_REPLACE:
                return RmaStatus::REPLACE_APPLY_PROCESSING;
            default:
                return RmaStatus::RETURN_OR_EXCHANGE_AVALIABLE;
        }

    }

    public function getResolutionTypeTitle($status)
    {
        if ($status == self::RESOLUTION_REFUND) {
            $resolution = 'Return';
        } elseif ($status == self::RESOLUTION_REPLACE) {
            $resolution = self::RESOLUTION_REPLACE_LABEL;
        } else {
            $resolution = self::RESOLUTION_CANCEL_LABEL;
        }

        return __($resolution);
    }

    /**
     * getAdminAndSellerPanelRmaStatusTitle 後台 RMA 標籤
     *
     * @param int $status
     * @return string
     */
    public function getAdminAndSellerPanelRmaStatusTitle($status)
    {
        $rmaLabel = ObjectManager::getInstance()->get(StatusLabel::class);
        $rmaStatus = $rmaLabel->getAdminAndSellerPanelRmaStatusTitle($status);

        return __($rmaStatus);

    }

    /**
     * getCustomerRmaStatusTitle 前台 RMA 標籤
     *
     * @param int $status
     * @return string
     */
    public function getCustomerRmaStatusTitle($status)
    {
        $rmaLabel = ObjectManager::getInstance()->get(StatusLabel::class);
        $rmaStatus = $rmaLabel->getCustomerRmaStatusTitle($status);

        return __($rmaStatus);

    }

    /**
     * getOrderStatusTitle 是否已經收到貨標籤
     *
     * @param mixed $status
     * @return void
     */
    public function getOrderStatusTitle($status)
    {
        if ($status == self::ORDER_DELIVERED) {
            $orderStatus = Shipping::ORDER_RECEIVED;
        } elseif ($status == self::ORDER_NOT_DELIVERED) {
            $orderStatus = Shipping::ORDER_NOT_RECEIVED;
        } else {
            $orderStatus = self::ORDER_NOT_APPLICABLE_LABEL;
        }

        return __($orderStatus);
    }

    /**
     * setItemsData 儲存退換貨商品資料
     *
     * @param array $productIds
     * @param int $reasonId
     * @param int $qty
     * @param int $price
     * @param int $rmaId
     * @return bool
     */
    public function setItemsData($productIds, $reasonId, $qty, $price, $rmaId)
    {
        try {
            foreach ($productIds as $itemId => $productId) {
                $data = [];
                $data['rma_id'] = $rmaId;
                $data['item_id'] = $itemId;
                $data['product_id'] = $productId;
                $data['qty'] = $qty;
                $data['price'] = $price;
                $data['reason_id'] = $reasonId;
                $this->saveItemData($data);
            }
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__);
            return false;
        }

        return true;
    }

    /**
     * Get Rma Items by Rma Id
     *
     * @param int $rmaId
     * @return \Webkul\MpRmaSystem\Model\Items
     */
    public function getRmaItemsByRmaId($rmaId)
    {
        $rmaColl = $this->items->create()->getCollection()->addFieldToFilter('rma_id', $rmaId);
        return $rmaColl;
    }

    /**
     * Override webkul getRmaItems
     * @param $rmaId
     * @return \Magento\Framework\DataObject|\Webkul\MpRmaSystem\Model\Items
     */
    public function getRmaItems($rmaId)
    {
        $rmaColl = $this->items->create()->getCollection()->addFieldToFilter('rma_id', $rmaId);
        return $rmaColl->getFirstItem();
    }

    public function getItemFinalPrice($item)
    {
        $shippingAmount = 0;
        $rmaQty = $item->getQty();
        $qty = $item->getQtyOrdered();
        $totalPrice = $item->getRowTotalInclTax();
        $discountAmount = (float)$item->getDiscountAmount() + (float)$item->getRowTotalPointDiscount();
        // $taxAmount = $item->getTaxAmount();
        // $finalPrice = $totalPrice + $taxAmount + $shippingAmount - $discountAmount;
        $finalPrice = $totalPrice + $shippingAmount - $discountAmount;
        $unitPrice = $finalPrice / $qty;
        $finalPrice = $unitPrice * $rmaQty;
        return $finalPrice;
    }

    /**
     * getItemFinalPoint
     *
     * @param mixed $item
     * @return int|string
     */
    public function getItemFinalPoint($item)
    {
        return $item->getRowTotalPointUsed() ?? 0;
    }

    /**
     * isNaturalPerson
     *
     * @param mixed $rma
     * @return bool
     */
    public function isNaturalPerson($rma)
    {
        return !$rma->getCustomerTaxIdNumber();
    }

    /**
     * Create Creditmemo
     *
     * @param array $data
     *
     * @return array
     */
    public function createCreditMemo($data)
    {
        $result = ['msg' => '', 'error' => ''];
        $rmaId = $data['rma_id'];
        $negative = $data['negative'];
        $rma = $this->getRmaDetails($rmaId);
        $stock = $data['back_to_stock'];
        $orderId = $rma->getOrderId();
        $orderData = $this->orderFactory->create()->load($orderId);
        $productDetails = $this->getRmaProductDetails($rmaId);
        $items = [];
        $shippingMethod = $orderData->getShippingMethod();

        foreach ($productDetails as $product) {
            if ($stock) {
                $items[$product->getItemId()] = ['qty' => $product->getQty(), 'back_to_stock' => $stock];
            } else {
                $items[$product->getItemId()] = ['qty' => $product->getQty()];
            }
        }

        $memoData = [
            'items' => $items,
            'do_offline' => (int)$data['do_offline'],
            'comment_text' => "",
            'shipping_amount' => (int)$data['seller_shipping_amount'],
            'adjustment_positive' => 0,
            'adjustment_negative' => $negative,
        ];

        try {

            if ($this->registry->registry('current_creditmemo')) {
                $this->registry->unregister('current_creditmemo');
            }

            $this->memoLoader->setOrderId($orderId);
            $this->memoLoader->setCreditmemoId("");
            $this->memoLoader->setCreditmemo($memoData);
            $this->memoLoader->setInvoiceId($this->getFirstInvoiceId($orderId));

            $order = $this->getOrder($orderId);
            $order->setForcedCanCreditmemo("true");
            $order->save();

            $memo = $this->memoLoader->load();

            if ($memo) {
                if (!$memo->isValidGrandTotal()) {
                    $result['msg'] = __('Total must be positive.');
                    $result['error'] = 1;
                    return $result;
                }

                if (!empty($memo['comment_text'])) {
                    $memo->addComment(
                        $memo['comment_text'],
                        isset($memo['comment_customer_notify']),
                        isset($memo['is_visible_on_front'])
                    );

                    $memo->setCustomerNote($memo['comment_text']);
                    $memo->setCustomerNoteNotify(isset($memo['comment_customer_notify']));
                }

                $grandTotal = $memo->getGrandTotal();

                if ($grandTotal >= 0) {
                    $this->creditmemoManagement->refund($memo, true, !empty($memo['send_email']));
                }

                if (!empty($memo['send_email'])) {
                    $this->memoSender->send($memo);
                }

                $result['msg'] = __('Credit memo generated successfully.');
                $result['error'] = 0;
                $result['memo_id'] = $memo->getId();
                return $result;
            }

            $result['msg'] = __('Unable to create credit memo right now.');
            $result['error'] = 1;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__, ['rma_id' => $rmaId]);
            $result['msg'] = $e->getMessage();
            $result['error'] = 1;
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__, ['rma_id' => $rmaId]);
            $result['msg'] = __('Unable to save credit memo right now.');
            $result['error'] = 1;
        }

        return $result;
    }

    /**
     * checkIsActiveRma
     *
     * @param mixed $item
     * @return bool
     */
    public function checkIsActiveRma($itemId)
    {
        $rmaColl = $this->items->create()
            ->getCollection()
            ->addFieldToFilter('item_id', $itemId);
        if (!$rmaColl->getSize()) {
            return false;
        } else {
            $rmaIds = $rmaColl->getColumnValues('rma_id');
            $rmaDetailsColl = $this->detailsCollection->create()
                ->addFieldToFilter('id', ['in' => $rmaIds])
                ->addFieldToFilter('status', ['nin' => [RmaStatus::RMA_CANCELED, RmaStatus::RETURN_APPLY_CANCEL, RmaStatus::REPLACE_APPLY_CANCEL]]);
            if ($rmaDetailsColl->getSize()) {
                return true;
            }
        }
        return false;
    }

    /**
     * getRmaIdByItem
     *
     * @param mixed $item
     * @return string | null
     */
    public function getRmaIdByItemId($itemId)
    {
        $rmaColl = $this->items->create()
            ->getCollection()
            ->addFieldToFilter('item_id', $itemId);
        return $rmaColl->getLastItem()->getRmaId() ?? '';
    }

    /**
     * Send New RMA Email
     *
     * @param array $details
     * @return void
     */
    public function sendNewRmaEmail($details = [])
    {
        $details['template'] = self::NEW_RMA;
        $resolutionType = $this->getResolutionTypeTitle($details['rma']['resolution_type']);
        $additionalInfo = $details['rma']['additional_info'];
        $customerName = $details['name'];
        $templateVars = [
            'name' => $customerName,
            'rma_id' => $details['rma']['rma_id'],
            'order_id' => $details['rma']['order_id'],
            'resolution_type' => $resolutionType->__toString(),
            'additional_info' => $additionalInfo,
            'rma_items_html' => $this->getRmaItemsHtml(
                $this->getRmaActions()->getRmaItemCollection($details['rma']['rma_id'])
            ),
        ];

        if (isset($details['order_data'])) {
            $templateVars['order_data'] = $details['order_data'];
        }

        $details['email'] = $details['rma']['customer_email'];
        if ($details['rma']['customer_id'] > 0) {
            $msg = __("New RMA is requested by customer.");
        } else {
            $msg = __("New RMA is requested by guest.");
        }

        $buyerEmailSubject = $details['rma']['resolution_type'] == 1 ? __('訂單退貨申請通知信') : __('訂單換貨申請通知信');
        $buyerEmailMessage = $details['rma']['resolution_type'] == 1 ? __('我們已收到您的退貨申請。') : __('我們已收到您的換貨申請。');
        //send to seller
        //        $sellerId = $details['rma']['seller_id'];
        //        if ($sellerId > 0) {
        //            $seller = $this->getCustomer($sellerId);
        //            $email = $seller->getEmail();
        //            $sellerName = $seller->getName();
        //            $templateVars['msg'] = $msg->__toString();
        //            $templateVars['name'] = $sellerName;
        //            $details['email'] = $email;
        //            $details['template_vars'] = $templateVars;
        //            try {
        //                $this->sendEmail($details);
        //            } catch (\Exception $e) {
        //                $this->messageManager->addError(__($e->getMessage()));
        //            }
        //        }

//        //send to admin
        //        if ($this->isAllowedNotification()) {
        //            $adminEmail = $this->getAdminEmail();
        //            $templateVars['msg'] = $msg->__toString();
        //            $templateVars['name'] = $this->getAdminName();
        //            $details['template_vars'] = $templateVars;
        //            $details['email'] = $adminEmail;
        //            try {
        //                $this->sendEmail($details);
        //            } catch (\Exception $e) {
        //                $this->messageManager->addError(__($e->getMessage()));
        //            }
        //
        //        }

        //send to customer/guest
        $msg = __("You requested new RMA.");
        $templateVars['msg'] = $msg->__toString();
        $templateVars['name'] = $customerName;
        $templateVars['rma_message'] = $buyerEmailMessage->render();
        $templateVars['rma_subject'] = $buyerEmailSubject->render();

        $details['template_vars'] = $templateVars;
        $details['email'] = $details['rma']['customer_email'];
        try {
            $this->sendEmail($details);
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__);
            $this->messageManager->addError(__($e->getMessage()));
        }
    }

    public function updateItemRefundColumnAfterIssueCreditMemo($memoId, $status = null)
    {

        $creditMemo = $this->creditmemoRepository->get($memoId);

        foreach ($creditMemo->getAllItems() as $creditmemoItem) {
            $orderItem = $creditmemoItem->getOrderItem();
            if ($status) {
                $orderItem->setFlowStatus($status);
            }
            $orderItem->save();
        }
    }

    public function sendUpdateRmaEmail($details = [])
    {
        return; // Karen request to disable this email
    }

    /**
     * @param Details $rmaDtails
     * @return false|void
     */
    public function getCreditMemoForRmaDetail(Details $rmaDtails)
    {
        try {
            return $this->creditmemoRepository->get((int)$rmaDtails->getMemoId());
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__);
            return false;
        }
    }

    /**
     * Create Creditmemo From PPS
     *
     * @param array $data
     *
     * @return array
     */
    public function createPPSCreditMemo($data)
    {
        $result = ['msg' => '', 'error' => ''];

        $memoData = [
            'items' => $data['items'],
            'do_offline' => true,
            'comment_text' => "Created By PPS Api",
            'shipping_amount' => (int)$data['shipping_amount'],
            'adjustment_positive' => 0,
            'adjustment_negative' => $data['negative'],
        ];

        try {
            if ($this->registry->registry('current_creditmemo')) {
                $this->registry->unregister('current_creditmemo');
            }

            $this->memoLoader->setOrderId($data['order_id']);
            $this->memoLoader->setCreditmemoId("");
            $this->memoLoader->setCreditmemo($memoData);
            $this->memoLoader->setInvoiceId($this->getFirstInvoiceId($data['order_id']));

            $order = $this->getOrder($data['order_id']);
            $order->setForcedCanCreditmemo("true");
            $order->save();

            $memo = $this->memoLoader->load();

            if ($memo) {
                if (!$memo->isValidGrandTotal()) {
                    $result['msg'] = __('Total must be positive.');
                    $result['error'] = 1;
                    return $result;
                }

                if (!empty($memo['comment_text'])) {
                    $memo->addComment(
                        $memo['comment_text'],
                        isset($memo['comment_customer_notify']),
                        true
                    );

                    $memo->setCustomerNote($memo['comment_text']);
                    $memo->setCustomerNoteNotify(isset($memo['comment_customer_notify']));
                }

                $grandTotal = $memo->getGrandTotal();

                if ($grandTotal >= 0) {
                    $this->creditmemoManagement->refund($memo, true, !empty($memo['send_email']));
                }

                if (!empty($memo['send_email'])) {
                    $this->memoSender->send($memo);
                }

                $result['msg'] = __('Credit memo generated successfully.');
                $result['error'] = 0;
                $result['memo_id'] = $memo->getId();
                return $result;
            }

            $result['msg'] = __('Unable to create credit memo right now.');
            $result['error'] = 1;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__, ['order_id' => $data['order_id'] ?? null]);
            $result['msg'] = $e->getMessage();
            $result['error'] = 1;
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__, ['order_id' => $data['order_id'] ?? null]);
            $result['msg'] = __('Unable to save credit memo right now.');
            $result['error'] = 1;
        }

        return $result;
    }

    private function getRmaItemsHtml($items)
    {
        $itemsHtml = '<ul>'; // Start an unordered list

        // Loop through each item in the array
        foreach ($items ?? [] as $item) {
            $itemsHtml .= '<li>' . $item->getName() . ' x ' . (int)$item->getQtyOrdered() . '</li>'; // Use <li> for each item
        }

        $itemsHtml .= '</ul>'; // Close the unordered list

        return $itemsHtml; // Return the formatted HTML
    }

    public function getRmaActions()
    {
        if (!$this->rmaActions) {
            $this->rmaActions = ObjectManager::getInstance()->get(RmaActions::class);
        }
        return $this->rmaActions;
    }

    public function getFirstInvoiceId($orderId)
    {
        $order = $this->getOrder($orderId);
        return $order->getInvoiceCollection()->getFirstItem()->getId();
    }

    public function isAllItemUnderReturned($order, $readyToReturnNumber)
    {
        if (((int)$order->getTotalQtyOrdered() -
                (int)$this->getRefundedItemsNum($order) -
                (int)$readyToReturnNumber) == 0) {

            return true;
        }

        return false;
    }

    protected function getRefundedItemsNum($order)
    {
        $qty = 0;
        foreach ($order->getAllVisibleItems() as $orderOriItems) {
            $qty = $qty + $orderOriItems->getQtyRefunded();
        }

        return $qty;
    }

    /**
     * @param $str
     * @return array|string|string[]|null
     */
    public function cleanSpecialChar($str)
    {
        $cleaned = $str;
        if (empty($str)) {
            $cleaned = preg_replace("/[^a-zA-Z0-9 ]/", "", $str);
        }
        return $cleaned;
    }
}
