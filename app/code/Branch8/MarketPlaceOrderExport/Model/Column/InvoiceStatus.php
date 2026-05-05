<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class InvoiceStatus implements ColumnInterface
{
    private $cached = [];

    public function getHeader()
    {
        return __('Invoice Status');
    }

    /***
     * Not sure this is Magento Invoice or ECpay Invoice number
     * @param array $row
     * @return string
     *
     */
    public function processColumnData(array $row = [])
    {
        /**
         * Temporary get sales_order.ecpay_invoice_number
         * @TODO will check with Karen if this is Magento Invoice need loop and get all Invoice Id
         */
        $this->cached[$row['order_id']] = $this->getStatus(
            $row['invoice_status']
        );
        return $this->cached[$row['order_id']];
    }

    /**
     * @param $value
     * @return \Magento\Framework\Phrase|string
     */
    private function getStatus($value)
    {
        //0 未開立, 1 發票開立, 2 發票作廢, 3 發票折讓, 4 不開發票, -1 開立失敗
        switch ($value) {
            case 0:
                $status = __('未開發票');
                break;
            case 1:
                $status = __('發票開立');
                break;
            case 2:
                $status = __('發票作廢');
                break;
            case 3:
                $status = __('發票折讓');
                break;
            case 4:
                $status = __('不開發票');
                break;
            case -1:
                $status = __('Failed To Issue');
                break;
            default:
                $status = '';
                break;
        }
        return $status;
    }
}
