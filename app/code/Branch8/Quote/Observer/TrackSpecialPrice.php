<?php

namespace Branch8\Quote\Observer;

use Branch8\Quote\Model\Actions\GetScheduleSpecialPriceMetaInformation;
use Magento\Framework\Event\ObserverInterface;
use Magento\Reports\Model\Event;

class TrackSpecialPrice implements ObserverInterface
{
    private GetScheduleSpecialPriceMetaInformation $getScheduleSpecialPriceMetaInformation;

    /**
     * @param GetScheduleSpecialPriceMetaInformation $getScheduleSpecialPriceMetaInformation
     */
    public function __construct(GetScheduleSpecialPriceMetaInformation $getScheduleSpecialPriceMetaInformation)
    {
        $this->getScheduleSpecialPriceMetaInformation = $getScheduleSpecialPriceMetaInformation;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var $quoteItem \Magento\Quote\Model\Quote\Item
         */
        $quoteItem = $observer->getEvent()->getItem();
        if (!$quoteItem->getId() && $quoteItem->getProduct()) {
            list($rowId,
                $specialPrice,
                $beginDate,
                $endDate
                ) = $this->getScheduleSpecialPriceMetaInformation->execute($quoteItem->getProduct());
            if ($specialPrice) {
                $quoteItem->setData('current_product_row_id', $rowId);
                $quoteItem->setData('schedule_change_special_price', $specialPrice);
                $quoteItem->setData('schedule_change_special_price_start', $beginDate);
                $quoteItem->setData('schedule_change_special_price_end', $endDate);
            }
        }
    }
}
