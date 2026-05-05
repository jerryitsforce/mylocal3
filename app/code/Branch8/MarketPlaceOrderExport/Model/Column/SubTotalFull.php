<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class SubTotalFull implements ColumnInterface
{
    public function getHeader()
    {
        return __('Hotai Row Total (P+$)');
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
                $subTotal = sprintf('0P + %s$', (int)$row['shipping_price_incl_tax']);
            } elseif ($isAdjustrow) {
                $subTotal = sprintf('0P + %s$', (int)$row['invoice_order_item_price']);
            } else {
                $subTotal = (int)$row['row_total_incl_tax'] - (int)$row['row_total_point_used'] - (int)$row['discount_amount'];
                $subTotal = sprintf('%sP + %s$', (int)$pointUsed, $subTotal);
            }
        } catch (NoSuchEntityException $exception) {
            $subTotal = '';
        }
        return $subTotal;
    }
}
