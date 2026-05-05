<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Model\AdminOrder;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderAddressInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrderFrontendUi\Model\Config;
use Magento\Framework\App\ObjectManager;

class Create extends \Magento\Sales\Model\AdminOrder\Create
{
    private $registry;

    /**
     * @return \Magento\Framework\Registry
     */
    private function getRegistry()
    {
        if ($this->registry === null) {
            $this->registry = ObjectManager::getInstance()->get(\Magento\Framework\Registry::class);
        }
        return $this->registry;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return array
     */
    private function getOrderItemsNeedToReorder(ParentOrder $parentOrder)
    {
        $collections = [];
        foreach ($parentOrder->getSubOrders() as $order) {
            foreach ($order->getItemsCollection($this->_salesConfig->getAvailableProductTypes(), true) as $orderItem) {
                /* @var $orderItem \Magento\Sales\Model\Order\Item */
                $collections[] = $orderItem;
            }
        }
        return $collections;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initFromParentOrder(ParentOrder $parentOrder)
    {
        $session = $this->getSession();
        $detail = $parentOrder->getDetail();
        $session->setData($parentOrder->getReordered() ? 'ParentReordered' : 'parent_order_id', $parentOrder->getId());
        $session->setOldParentOrderId($parentOrder->getId());
        //$this->getCoreSession()->setOldParentOrderId($parentOrder->getId());
        $session->setCurrencyId($detail->getOrderCurrencyCode());
        /* Check if we edit guest order */
        $session->setCustomerId($detail->getCustomerId() ?: false);
        $session->setStoreId($detail->getStoreId());
        $this->getQuote()->setCustomerGroupId($detail->getCustomerGroupId());

        /* Initialize catalog rule data with new session values */
        $this->initRuleData();
        $this->getRegistry()->register('allow_reorder_oos_product', true);
        foreach ($this->getOrderItemsNeedToReorder($parentOrder) as $orderItem) {
            /* @var $orderItem \Magento\Sales\Model\Order\Item */
            if (!$orderItem->getParentItem()) {
                $orderedQty = $qty = $orderItem->getQtyOrdered();
                if (!$parentOrder->getReordered()) {
                    $qty -= max($orderItem->getQtyShipped(), $orderItem->getQtyInvoiced());
                }
                if ($qty <= 0) {
                    $qty = $orderedQty;
                }
                if ($qty > 0) {
                    $item = $this->initFromOrderItem($orderItem, $qty);
                    if (is_string($item)) {
                        throw new \Magento\Framework\Exception\LocalizedException(__($item));
                    }
                }
            }
        }
        $shippingAddress = $parentOrder->getShippingAddress();
        if ($shippingAddress) {
            $shippingAddress->setSameAsBilling(
                $this->isAddressesAreEqual($parentOrder)
            );
        }

        $this->_initBillingAddressFromParentOrder($parentOrder);
        $this->_initShippingAddressFromParentOrder($parentOrder);

        $quote = $this->getQuote();
        if (!$quote->isVirtual() && $this->getShippingAddress()->getSameAsBilling()) {
            $quote->getShippingAddress()->setCustomerAddressId(
                $quote->getBillingAddress()->getCustomerAddressId()
            );
            $this->setShippingAsBilling(1);
        }
        $this->setShippingMethod(
            $detail->getShippingMethod()
        );
        $quote->getShippingAddress()->setShippingDescription(
            $detail->getShippingDescription()
        );
        $quote->collectTotals();
        $this->_objectCopyService->copyFieldsetToTarget(
            'sales_copy_order',
            'to_edit',
            $detail,
            $quote
        );
        $this->_eventManager->dispatch(
            'sales_convert_parent_order_to_quote',
            [
                'parent_order' => $parentOrder,
                'quote' => $quote
            ]
        );

        if (!$detail->getCustomerId()) {
            $quote->setCustomerIsGuest(true);
        }

        if ($session->getUseOldShippingMethod(true)) {
            /*
             * if we are making reorder or editing old order
             * we need to show old shipping as preselected
             * so for this we need to collect shipping rates
             */
            $this->collectShippingRates();
        } else {
            /*
             * if we are creating new order then we don't need to collect
             * shipping rates before customer hit appropriate button
             */
            $this->collectRates();
        }

        $quote->getShippingAddress()->unsCachedItemsAll();
        $quote->getBillingAddress()->unsCachedItemsAll();
        $quote->setTotalsCollectedFlag(false);

        $this->quoteRepository->save($quote);
        try {
            // unset registry key to prevent issue
            if ($this->getRegistry()->registry('allow_reorder_oos_product')) {
                $this->getRegistry()->unregister('allow_reorder_oos_product');
            }
        } catch (\Exception $exception) {
            $this->_logger->critical($exception);
        }
        return $this;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return bool
     */
    private function isAddressesAreEqual(ParentOrder $parentOrder)
    {
        $shippingAddress = $parentOrder->getShippingAddress();
        if (empty($shippingAddress)) {
            return true;
        }
        $billingAddress = $parentOrder->getBillingAddress();
        $shippingData = $this->getDataObjectConverter()->toFlatArray(
            $shippingAddress, [],
            ParentOrderAddressInterface::class
        );
        $billingData = $this->getDataObjectConverter()->toFlatArray(
            $billingAddress, [], ParentOrderAddressInterface::class
        );
        unset(
            $shippingData['address_type'],
            $shippingData['entity_id'],
            $billingData['address_type'],
            $billingData['entity_id']
        );
        if (isset($shippingData['customer_address_id']) && !isset($billingData['customer_address_id'])) {
            unset($shippingData['customer_address_id']);
        }

        return $shippingData == $billingData;
    }

    /**
     * @return \Magento\Framework\Api\ExtensibleDataObjectConverter|mixed
     */
    private function getDataObjectConverter()
    {
        return $this->_objectManager->get(\Magento\Framework\Api\ExtensibleDataObjectConverter::class);
    }

    /**
     * @param ParentOrder $parentOrder
     * @return void
     */
    protected function _initBillingAddressFromParentOrder(ParentOrder $parentOrder)
    {
        $this->getQuote()->getBillingAddress()->setCustomerAddressId('');
        $this->_objectCopyService->copyFieldsetToTarget(
            'sales_copy_order_billing_address',
            'to_order',
            $parentOrder->getBillingAddress(),
            $this->getQuote()->getBillingAddress()
        );
    }

    /**
     * @param ParentOrder $parentOrder
     * @return void
     */
    protected function _initShippingAddressFromParentOrder(ParentOrder $parentOrder)
    {
        $orderShippingAddress = $parentOrder->getShippingAddress();
        $quoteShippingAddress = $this->getQuote()->getShippingAddress()->setCustomerAddressId(
            ''
        )->setSameAsBilling(
            $orderShippingAddress && $orderShippingAddress->getSameAsBilling()
        );
        $this->_objectCopyService->copyFieldsetToTarget(
            'sales_copy_order_shipping_address',
            'to_order',
            $orderShippingAddress,
            $quoteShippingAddress
        );
    }

    /**
     * @return \Magento\Framework\Session\SessionManager|mixed
     */
    private function getCoreSession()
    {
        return ObjectManager::getInstance()->get(\Magento\Framework\Session\SessionManager::class);
    }
}
