<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model;

use Branch8\MarketPlaceOrderExport\Model\EcpayLogWriter;

use Branch8\MarketPlaceOrderExport\Model\Writer;
use Magento\Framework\Exception\LocalizedException;

class SellerRevenueReportWriter extends Writer
{
    /**
     * @return $this|EcpayLogWriter
     * @throws LocalizedException
     */
    public function writeRecords()
    {
        foreach ($this->records as $row) {
            $record = $this->buildRecord($row);
            // isHeader set to false (2nd param), autoSize set to false (3rd param)
            $this->writeRecord(array_values($record), false, false);
        }
        return $this;
    }
}
