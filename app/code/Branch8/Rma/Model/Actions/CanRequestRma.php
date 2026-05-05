<?php
declare(strict_types=1);

namespace Branch8\Rma\Model\Actions;
class CanRequestRma
{
    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return true
     */
    public function execute(\Magento\Sales\Model\Order\Item $item)
    {
        return true;
    }
}
