<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\App\ResourceConnection;

class InvoiceNumber implements ColumnInterface
{
    private $cached = [];
    /***
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    )
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function getHeader()
    {
        return __('Invoice Number');
    }

    /***
     * Not sure this is Magento Invoice or ECpay Invoice number
     * @param array $row
     * @return string
     *
     */
    public function processColumnData(array $row = [])
    {

        $this->cached[$row['order_id']] = (string)$row['invoice_number'];
        return $this->cached[$row['order_id']];
    }
}
