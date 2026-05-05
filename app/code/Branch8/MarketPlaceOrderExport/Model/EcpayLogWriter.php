<?php

namespace Branch8\MarketPlaceOrderExport\Model;


use Magento\Framework\Exception\LocalizedException;

class EcpayLogWriter extends Writer
{
    /**
     * @return $this|EcpayLogWriter
     * @throws LocalizedException
     */
    public function writeRecords()
    {
        foreach ($this->records as $row) {
            $record = $this->buildRecord($row);
            if ($row['item_type'] !== 'item') {
                $this->setNulLColumn($record);
            }
            //$this->records[] = array_values($record);
            $this->writeRecord(array_values($record));
        }
        return $this;
    }
}
