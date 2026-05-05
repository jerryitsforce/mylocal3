<?php

namespace Branch8\Marketplace\Model\Export;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Sales\Model\OrderRepository;

class Writer extends AbstractWriter
{
    protected OrderRepository $orderRepository;

    /**
     * @param Filesystem $filesystem
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        Filesystem             $filesystem,
        OrderRepository        $orderRepository
    )
    {
        parent::__construct($filesystem);
        $this->orderRepository = $orderRepository;
    }

    /***
     * @return $this|Writer
     * @throws LocalizedException
     */
    public function writeRecords()
    {
        $recordSet = $this->data;
        foreach ($recordSet as $record) {
            $style = [];
            if (!empty($record['return_order_number']) && !empty($record['rma_status']) && !in_array($record['rma_status'], ['returned_cancel','replace_cancel'])) {
                $style['background'] = 'red';
            }
            $this->writeRecord($record, false, $style);
        }
        return $this;
    }

    public function writeHeader()
    {
        $header = \Branch8\MarketPlaceSeller\Helper\OrderDailyNotificationHelper::HK_HEADER;
        $this->writeRecord($header, true);
        return $this;
    }
}


