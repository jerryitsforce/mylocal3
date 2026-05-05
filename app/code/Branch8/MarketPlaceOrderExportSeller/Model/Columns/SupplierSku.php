<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model\Columns;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\App\ResourceConnection;

class SupplierSku extends \Branch8\MarketPlaceOrderExport\Model\Column\SupplierSKU implements ColumnInterface
{
    public function getHeader()
    {
        return __('Supplier Sku');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {

      return parent::processColumnData($row);
    }

}
