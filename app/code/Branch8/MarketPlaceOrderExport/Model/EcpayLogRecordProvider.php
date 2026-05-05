<?php

namespace Branch8\MarketPlaceOrderExport\Model;

use Branch8\MarketPlaceOrderExport\Model\Services\GetEcpayInvoiceOrderLog;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManager\ObjectManager;
use Branch8\MarketPlaceOrderExport\Helper\Logger as LoggerInterface;

class EcpayLogRecordProvider extends RecordsProvider
{
    /**
     * @return array|mixed
     * @throws LocalizedException
     */
    public function getOrderItemRecords()
    {
        $this->getAllRecordItems();
        return $this->allOrderItemRecords;
    }
}


