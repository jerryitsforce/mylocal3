<?php
namespace Branch8\CartItemPosition\Plugin\Magento\Quote\Model\Quote;

class SetCartItemPosition
{
    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $resultItem
     * @param $itemId
     * @return mixed
     */
    public function afterUpdateItem(\Magento\Quote\Model\Quote $quote, $resultItem, $itemId)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/sorting.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($resultItem->getId() .' === '. $itemId);
        if($resultItem->getId() !== $itemId){
            $item = $quote->getItemById($itemId);
            $itemPosition = $item->getPosition();

            $resultItem->setPosition($itemPosition);
        }
        return $resultItem;
    }

    /**
     * @param $collection
     * @return mixed
     */
    public function afterGetItemsCollection(\Magento\Quote\Model\Quote $quote, $collection)
    {
        $collection->addOrder('position', 'ASC');
        return $collection;
    }
}

