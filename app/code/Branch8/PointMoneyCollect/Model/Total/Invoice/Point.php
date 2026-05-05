<?php

namespace Branch8\PointMoneyCollect\Model\Total\Invoice;

class Point extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $pointDiscount = $order->getPointDiscountTotal();

        if ($pointDiscount) {
            $invoice->setPointUsedTotal($pointDiscount);
           /* $invoice->setDiscountAmount($invoice->getDiscountAmount() + $pointDiscount);
            $invoice->setBaseDiscountAmount($invoice->getBaseDiscountAmount() + $pointDiscount);*/

            $invoice->setGrandTotal($invoice->getGrandTotal() - $pointDiscount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() - $pointDiscount);
        }

        return $this;
    }
}
