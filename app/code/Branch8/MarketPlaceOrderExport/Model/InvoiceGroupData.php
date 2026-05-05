<?php

namespace Branch8\MarketPlaceOrderExport\Model;
/**
 * Group All Record Follow Invoice Number
 */
class InvoiceGroupData implements InvoiceGroupDataInterface
{
    const NO_INVOICE = 'NO_INVOICE';
    private $invoiceNumber;

    private $status;
    private int $invoiceStatus;

    private $records = [];

    public function setInvoiceNumber(string $invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;
        return $this;
    }

    public function getInvoiceNumber(): string
    {
        return (string)$this->invoiceNumber;
    }

    /**
     * @param int $invoiceStatus
     * @return InvoiceGroupData
     */
    public function setStatus(int $invoiceStatus)
    {
        $this->invoiceStatus = $invoiceStatus;
        return $this;
    }

    /**
     * @param $row
     * @return $this|InvoiceGroupInterface
     */
    public function addRecord($row)
    {
        $this->records[] = $row;
        return $this;
    }

    /**
     * @return int
     */
    public function getInvoiceStatus(): int
    {
        return $this->invoiceStatus;
    }

    /**
     * @return array
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * @return $this|InvoiceGroupDataInterface
     */
    public function reset()
    {
        $this->records = [];
        $this->status = '';
        $this->invoiceNumber = '';
        return $this;
    }
}
