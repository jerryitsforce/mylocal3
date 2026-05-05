<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class SupplierSKU implements ColumnInterface
{
    public function getHeader()
    {
        return __('Supplier Sku');
    }

    public function processColumnData(array $row = [])
    {
        $originSku = $row['origin_sku'] ?? '';
        if (empty($originSku)) {
            return '';
        }
        // has_options 判斷為多規商品，回 option_sku
        if (isset($row['has_options']) && $row['has_options'] == 1) {
            if (!empty($row['variation_sku'])) {
                return '="' . $row['variation_sku'] . '"';
            } else if (!empty($row['option_sku'])) {
                return '="' . $row['option_sku'] . '"';
            }
        }

        // 單規商品，提取第一個dash後的部分
        $position = strpos($originSku, '-');
        if ($position !== false) {
            return '="' . substr($originSku, $position + 1) . '"';
        }

        return '="' . $originSku . '"';
    }
}
