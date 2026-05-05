<?php

namespace Branch8\OptionsWithStockAndImages\Observer;

use Magento\Catalog\Model\Product\Option;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Webkul\OptionsWithStockAndImages\Logger\Logger;

class OptionSaveBefore implements ObserverInterface
{
    /**
     * @var Logger
     */
    public $logger;

    /**
     * Constructor
     *
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Sales Order Place After event handler.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            $option = $observer->getEvent()->getData('data_object');
            if ($option instanceof Option) {
                $option->setData('store_id', 0);
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
