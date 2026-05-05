<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class SubTotal implements ColumnInterface
{
    public function getHeader()
    {
        return __('Hotai Row Total');
    }

    /**
     * @param array $row
     * @return string
     */
    public function processColumnData(array $row = [])
    {
        try {
            $rowTotal = (int)$row['row_total_incl_tax'];
            $pointUsed = (int)$row['row_total_point_used'];
            $discount = (int)$row['discount_amount'];
            $isAdjustrow = $row['invoice_order_item_name'] === '訂單處理費';
            if ($row['item_type'] === 'shipping') {
                $subTotal = sprintf('%s', (int)$row['shipping_price_incl_tax']);
            } elseif ($isAdjustrow) {
                $subTotal = sprintf('%s', (int)$row['invoice_order_item_price']);
            } else {
                $_total = (int)$row['row_total_incl_tax'] - (int)$row['row_total_point_used'] - (int)$row['discount_amount'];
                if ($_total == 0 && $row['row_total_point_used'] > 0) {
                    $subTotal = (int)$row['row_total_point_used'];
                } else {
                    $subTotal = (int)$row['row_total_incl_tax'] - (int)$row['discount_amount'];
                }
            }
        } catch (NoSuchEntityException $exception) {
            $subTotal = '';
        }
        return $subTotal;
    }
}
