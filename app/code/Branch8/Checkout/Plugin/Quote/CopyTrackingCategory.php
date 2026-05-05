<?php

namespace Branch8\Checkout\Plugin\Quote;

class CopyTrackingCategory{
    /**
     * Copy category name and id referer from quote item to order items
     * 
     * @param \Magento\Quote\Model\Quote\Item\ToOrderItem $subject
     * @param $resultOrderItem
     * @param $item
     * @param $data
     * @return mixed
     */
    public function afterConvert(\Magento\Quote\Model\Quote\Item\ToOrderItem $subject, $resultOrderItem, $item, $data){
        if ($item->getCategoryId()) {
            $resultOrderItem->setCategoryName($item->getCategoryName());
            $resultOrderItem->setCategoryId($item->getCategoryId());
        }

        return $resultOrderItem;
    }
}