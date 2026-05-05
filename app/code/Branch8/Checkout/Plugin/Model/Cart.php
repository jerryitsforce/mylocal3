<?php

namespace Branch8\Checkout\Plugin\Model;

class Cart
{
    protected $messageManager;

    protected $_eventManager;
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ){
        $this->_eventManager = $eventManager;
        $this->messageManager = $messageManager;
    }
    public function aroundUpdateItems($subject, $process, $data)
    {
        $infoDataObject = new \Magento\Framework\DataObject($data);
        $this->_eventManager->dispatch(
            'checkout_cart_update_items_before',
            ['cart' => $subject, 'info' => $infoDataObject]
        );

        $qtyRecalculatedFlag = false;
        $itemErrors = [];
        foreach ($data as $itemId => $itemInfo) {
            $item = $subject->getQuote()->getItemById($itemId);
            if (!$item || !$item->getAvailableToCheckout()) {
                continue;
            }

            if (!empty($itemInfo['remove']) || isset($itemInfo['qty']) && $itemInfo['qty'] == '0') {
                $subject->removeItem($itemId);
                continue;
            }

            $qty = isset($itemInfo['qty']) ? (double)$itemInfo['qty'] : false;
            if ($qty > 0) {
                $item->clearMessage();
                $item->setHasError(false);
                $item->setQty($qty);

                if ($item->getHasError()) {
                    $itemErrors[$item->getId()] = __($item->getMessage());
                }

                if (isset($itemInfo['before_suggest_qty']) && $itemInfo['before_suggest_qty'] != $qty) {
                    $qtyRecalculatedFlag = true;
                    $this->messageManager->addNoticeMessage(
                        __('Quantity was recalculated from %1 to %2', $itemInfo['before_suggest_qty'], $qty),
                        'quote_item' . $item->getId()
                    );
                }
            }
        }

        if ($qtyRecalculatedFlag) {
            $this->messageManager->addNoticeMessage(
                __('We adjusted product quantities to fit the required increments.')
            );
        }

        $this->_eventManager->dispatch(
            'checkout_cart_update_items_after',
            ['cart' => $subject, 'info' => $infoDataObject]
        );

        if (count($itemErrors)) {
            throw new \Magento\Framework\Exception\LocalizedException(current($itemErrors));
        }

        return $subject;
    }
}