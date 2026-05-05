<?php

namespace Branch8\MarketPlaceOrderExport\Model;
/**
 * Group All Record Follow Invoice Number
 */
interface InvoiceGroupDataInterface
{
    /**
     * @param string $invoiceNumber
     * @return InvoiceGroupDataInterface
     */
    public function setInvoiceNumber(string $invoiceNumber);

    /**
     * @return string
     */
    public function getInvoiceNumber(): string;

    /**
     * @param int $invoiceStatus
     * @return InvoiceGroupDataInterface
     */
    public function setStatus(int $invoiceStatus);

    /**
     * @param array $row
     * @return InvoiceGroupDataInterface
     */
    public function addRecord($row);

    /**
     * @return array
     */
    public function getRecords(): array;

    /**
     * @return InvoiceGroupDataInterface
     */
    public function reset();
}
